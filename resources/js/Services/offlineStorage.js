/**
 * Pirgacha Internet — Native IndexedDB & Offline Queue Engine (Milestone 13)
 * Provides indexed storage for Customers, Packages, Areas, Complaints, and Mutation Queue.
 */

const DB_NAME = 'PirgachaIspOfflineDB';
const DB_VERSION = 3;

let dbInstance = null;

export function openOfflineDatabase() {
    if (dbInstance && dbInstance.version >= DB_VERSION) {
        return Promise.resolve(dbInstance);
    }

    if (dbInstance) {
        try { dbInstance.close(); } catch (e) {}
        dbInstance = null;
    }

    return new Promise((resolve, reject) => {
        if (!('indexedDB' in window)) {
            reject(new Error('IndexedDB is not supported on this browser.'));
            return;
        }

        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onblocked = () => {
            console.warn('IndexedDB upgrade blocked. Please reload any other open tabs.');
        };

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            const transaction = event.target.transaction;

            // 1. Customers Store
            let customerStore;
            if (!db.objectStoreNames.contains('customers')) {
                customerStore = db.createObjectStore('customers', { keyPath: 'id' });
            } else {
                customerStore = transaction.objectStore('customers');
            }

            // Ensure customer_code index is strictly NON-UNIQUE
            if (customerStore.indexNames.contains('customer_code')) {
                customerStore.deleteIndex('customer_code');
            }
            customerStore.createIndex('customer_code', 'customer_code', { unique: false });

            if (!customerStore.indexNames.contains('name')) {
                customerStore.createIndex('name', 'name', { unique: false });
            }
            if (!customerStore.indexNames.contains('phone')) {
                customerStore.createIndex('phone', 'phone', { unique: false });
            }
            if (!customerStore.indexNames.contains('pppoe_username')) {
                customerStore.createIndex('pppoe_username', 'pppoe_username', { unique: false });
            }

            // 2. Packages Store
            if (!db.objectStoreNames.contains('packages')) {
                db.createObjectStore('packages', { keyPath: 'id' });
            }

            // 3. Areas Store
            if (!db.objectStoreNames.contains('areas')) {
                db.createObjectStore('areas', { keyPath: 'id' });
            }

            // 4. Complaints Store
            if (!db.objectStoreNames.contains('complaints')) {
                const complaintStore = db.createObjectStore('complaints', { keyPath: 'id' });
                complaintStore.createIndex('customer_id', 'customer_id', { unique: false });
                complaintStore.createIndex('status', 'status', { unique: false });
            }

            // 5. Offline Mutation Queue Store
            if (!db.objectStoreNames.contains('mutations')) {
                const mutationStore = db.createObjectStore('mutations', { keyPath: 'uuid' });
                mutationStore.createIndex('status', 'status', { unique: false });
                mutationStore.createIndex('created_at', 'created_at', { unique: false });
            }

            // 6. Meta Store (last_sync, server_version, device_id)
            if (!db.objectStoreNames.contains('meta')) {
                db.createObjectStore('meta', { keyPath: 'key' });
            }
        };

        request.onsuccess = (event) => {
            dbInstance = event.target.result;
            resolve(dbInstance);
        };

        request.onerror = (event) => {
            reject(event.target.error);
        };
    });
}

/**
 * Save collection of records to an Object Store.
 */
export async function saveToStore(storeName, items, clearFirst = false) {
    if (!items || !Array.isArray(items)) {
        return true;
    }

    const db = await openOfflineDatabase();
    return new Promise((resolve, reject) => {
        try {
            const tx = db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);

            if (clearFirst) {
                store.clear();
            }

            items.forEach((item) => {
                if (item && item.id !== undefined) {
                    store.put(item);
                }
            });

            tx.oncomplete = () => resolve(true);
            tx.onerror = () => {
                console.error(`saveToStore error in ${storeName}:`, tx.error);
                reject(tx.error);
            };
            tx.onabort = () => {
                console.error(`saveToStore aborted in ${storeName}:`, tx.error);
                reject(tx.error);
            };
        } catch (e) {
            console.error(`Transaction failed for store ${storeName}:`, e);
            reject(e);
        }
    });
}

/**
 * Get all records from an Object Store.
 */
export async function getAllFromStore(storeName) {
    const db = await openOfflineDatabase();
    return new Promise((resolve, reject) => {
        try {
            const tx = db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const req = store.getAll();

            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        } catch (e) {
            console.warn(`Error reading from store ${storeName}:`, e);
            resolve([]);
        }
    });
}

/**
 * Get single record by key from an Object Store.
 */
export async function getFromStore(storeName, key) {
    const db = await openOfflineDatabase();
    return new Promise((resolve, reject) => {
        try {
            const tx = db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const req = store.get(key);

            req.onsuccess = () => resolve(req.result || null);
            req.onerror = () => reject(req.error);
        } catch (e) {
            console.warn(`Error getting key ${key} from store ${storeName}:`, e);
            resolve(null);
        }
    });
}

/**
 * Set metadata value.
 */
export async function setMeta(key, value) {
    const db = await openOfflineDatabase();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('meta', 'readwrite');
        tx.objectStore('meta').put({ key, value });
        tx.oncomplete = () => resolve(true);
        tx.onerror = () => reject(tx.error);
    });
}

/**
 * Get metadata value.
 */
export async function getMeta(key) {
    const db = await openOfflineDatabase();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('meta', 'readonly');
        const req = tx.objectStore('meta').get(key);
        req.onsuccess = () => resolve(req.result ? req.result.value : null);
        req.onerror = () => reject(req.error);
    });
}

/**
 * Enqueue an offline mutation.
 */
export async function queueMutation(action, payload) {
    const db = await openOfflineDatabase();
    const uuid = 'mut_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);

    const mutation = {
        uuid,
        action,
        payload,
        status: 'pending', // pending, syncing, completed, failed, conflict
        retry_count: 0,
        error_message: null,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
    };

    return new Promise((resolve, reject) => {
        const tx = db.transaction('mutations', 'readwrite');
        tx.objectStore('mutations').put(mutation);
        tx.oncomplete = () => resolve(mutation);
        tx.onerror = () => reject(tx.error);
    });
}

/**
 * Get pending mutations count.
 */
export async function getPendingMutationsCount() {
    const db = await openOfflineDatabase();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('mutations', 'readonly');
        const store = tx.objectStore('mutations');
        const req = store.getAll();

        req.onsuccess = () => {
            const pending = (req.result || []).filter(m => m.status === 'pending' || m.status === 'failed');
            resolve(pending.length);
        };
        req.onerror = () => reject(req.error);
    });
}

/**
 * Remove or mark completed mutations.
 */
export async function updateMutationStatus(uuid, status, errorMessage = null) {
    const db = await openOfflineDatabase();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('mutations', 'readwrite');
        const store = tx.objectStore('mutations');
        const req = store.get(uuid);

        req.onsuccess = () => {
            const mut = req.result;
            if (mut) {
                if (status === 'completed') {
                    store.delete(uuid);
                } else {
                    mut.status = status;
                    mut.error_message = errorMessage;
                    mut.updated_at = new Date().toISOString();
                    if (status === 'failed') {
                        mut.retry_count = (mut.retry_count || 0) + 1;
                    }
                    store.put(mut);
                }
            }
        };

        tx.oncomplete = () => resolve(true);
        tx.onerror = () => reject(tx.error);
    });
}

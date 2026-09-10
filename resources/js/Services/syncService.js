/**
 * Pirgacha Internet — Synchronization Service (Milestone 13)
 * Orchestrates full cache downloads and flushes pending mutations to Laravel API.
 */

import axios from 'axios';
import {
    saveToStore,
    getAllFromStore,
    getFromStore,
    setMeta,
    getMeta,
    queueMutation,
    updateMutationStatus,
    getPendingMutationsCount
} from './offlineStorage';

export const syncService = {
    /**
     * Download and update local IndexedDB cache with latest server dataset.
     */
    async downloadBootstrapCache() {
        if (!navigator.onLine) {
            throw new Error('Cannot sync cache while offline.');
        }

        try {
            const res = await axios.get(route('staff.sync.bootstrap'));
            if (res.data && res.data.success) {
                const { customers, packages, areas, complaints } = res.data.data;

                if (customers?.length) await saveToStore('customers', customers);
                if (packages?.length) await saveToStore('packages', packages);
                if (areas?.length) await saveToStore('areas', areas);
                if (complaints?.length) await saveToStore('complaints', complaints);

                await setMeta('last_sync', res.data.timestamp);
                await setMeta('server_version', res.data.server_version);

                return {
                    success: true,
                    timestamp: res.data.timestamp,
                    counts: {
                        customers: customers?.length || 0,
                        packages: packages?.length || 0,
                        areas: areas?.length || 0,
                        complaints: complaints?.length || 0,
                    },
                };
            }
            throw new Error('Invalid sync payload from server.');
        } catch (error) {
            console.error('Bootstrap sync failed:', error);
            throw error;
        }
    },

    /**
     * Flush pending offline mutations to the server.
     */
    async flushPendingMutations() {
        if (!navigator.onLine) {
            return { processed: 0, remaining: await getPendingMutationsCount() };
        }

        const allMutations = await getAllFromStore('mutations');
        const pending = allMutations.filter(m => m.status === 'pending' || m.status === 'failed');

        if (pending.length === 0) {
            return { processed: 0, remaining: 0 };
        }

        // Mark as syncing
        for (const mut of pending) {
            await updateMutationStatus(mut.uuid, 'syncing');
        }

        try {
            const res = await axios.post(route('staff.sync.mutations'), {
                mutations: pending.map(m => ({
                    uuid: m.uuid,
                    action: m.action,
                    payload: m.payload,
                    timestamp: m.created_at,
                })),
            });

            if (res.data && res.data.success) {
                for (const result of res.data.results) {
                    if (result.status === 'success') {
                        await updateMutationStatus(result.uuid, 'completed');
                    } else if (result.status === 'conflict') {
                        await updateMutationStatus(result.uuid, 'conflict', result.message);
                    } else {
                        await updateMutationStatus(result.uuid, 'failed', result.message);
                    }
                }
            }
        } catch (error) {
            console.error('Mutation flush error:', error);
            // Revert syncing back to failed for retry
            for (const mut of pending) {
                await updateMutationStatus(mut.uuid, 'failed', error.message);
            }
        }

        const remaining = await getPendingMutationsCount();
        return {
            processed: pending.length,
            remaining,
        };
    },

    /**
     * Search customers locally inside IndexedDB when offline.
     */
    async searchOfflineCustomers(query) {
        if (!query || query.trim().length < 2) return [];

        const q = query.trim().toLowerCase();
        const allCustomers = await getAllFromStore('customers');

        return allCustomers.filter(c => {
            return (
                (c.customer_code && c.customer_code.toLowerCase().includes(q)) ||
                (c.name && c.name.toLowerCase().includes(q)) ||
                (c.phone && c.phone.includes(q)) ||
                (c.pppoe_username && c.pppoe_username.toLowerCase().includes(q)) ||
                (c.area_name && c.area_name.toLowerCase().includes(q))
            );
        }).slice(0, 10);
    },

    /**
     * Collect payment offline and enqueue mutation.
     */
    async collectPaymentOffline(customer, formPayload) {
        // Enqueue mutation
        const mut = await queueMutation('collect_payment', {
            customer_id: customer.id,
            amount: formPayload.amount,
            payment_method: formPayload.payment_method,
            notes: formPayload.notes || 'Offline field collection',
        });

        // Optimistically update local customer balance
        const localCustomer = await getFromStore('customers', customer.id);
        if (localCustomer) {
            localCustomer.balance = (localCustomer.balance || 0) + parseFloat(formPayload.amount);
            await saveToStore('customers', [localCustomer]);
        }

        // Attempt flush immediately if online
        if (navigator.onLine) {
            this.flushPendingMutations().catch(() => {});
        }

        return mut;
    },

    /**
     * Renew connection offline and enqueue mutation.
     */
    async renewConnectionOffline(customer, connectionId, formPayload) {
        const mut = await queueMutation('renew_connection', {
            customer_id: customer.id,
            connection_id: connectionId,
            validity_days: formPayload.validity_days,
            is_zero_charge: formPayload.is_zero_charge || false,
            mode: formPayload.mode || (formPayload.is_zero_charge ? 'deduct_shift' : 'standard'),
            collect_payment: formPayload.collect_payment,
            payment_method: formPayload.payment_method,
            notes: formPayload.notes || 'Offline field renewal',
        });

        if (navigator.onLine) {
            this.flushPendingMutations().catch(() => {});
        }

        return mut;
    },

    /**
     * Get last sync timestamp and pending mutations count.
     */
    async getSyncStatus() {
        const lastSync = await getMeta('last_sync');
        const pendingCount = await getPendingMutationsCount();
        return {
            lastSync,
            pendingCount,
        };
    }
};

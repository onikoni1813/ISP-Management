<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Areas (Hierarchical: Area -> Zone -> Sub-zone)
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('status')->default('active'); // active, inactive
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Packages
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedInteger('speed_mbps')->default(10);
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, inactive, archived
            $table->timestamps();
        });

        // 3. Package Prices (Historical Versioning)
        Schema::create('package_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('validity_days')->default(30);
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->string('status')->default('active'); // active, superseded
            $table->timestamps();

            $table->index(['package_id', 'status']);
        });

        // 4. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique(); // e.g. CUST-000001
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // for portal login
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('status')->default('active'); // active, expired, suspended, disconnected, pending, archived
            $table->date('join_date');
            $table->unsignedTinyInteger('billing_day')->default(1);
            $table->decimal('balance', 10, 2)->default(0.00); // Net due / advance balance
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_code', 'name', 'status']);
        });

        // 5. Customer Contacts
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('contact_type')->default('primary'); // primary, secondary, emergency, billing
            $table->string('name')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'phone']);
        });

        // 6. Customer Addresses
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('address_type')->default('installation'); // installation, billing
            $table->string('house_no')->nullable();
            $table->string('road_no')->nullable();
            $table->string('village_or_area')->nullable();
            $table->string('post_office')->nullable();
            $table->string('police_station')->default('Pirgacha');
            $table->string('district')->default('Rangpur');
            $table->text('full_address');
            $table->timestamps();
        });

        // 7. Connections (Customer and Connection are separated)
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->string('connection_code')->unique(); // e.g. CON-000001
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('current_package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->string('protocol')->default('pppoe'); // pppoe, static, dhcp
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('router_model')->nullable();
            $table->string('fiber_box_id')->nullable();
            $table->string('status')->default('active'); // active, expired, suspended, disconnected
            $table->date('installation_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            $table->index(['connection_code', 'status']);
            $table->index(['ip_address', 'mac_address']);
        });

        // 8. PPPoE Credentials (Encrypted Password)
        Schema::create('pppoe_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained()->cascadeOnDelete();
            $table->string('username')->unique();
            $table->text('password'); // Laravel encrypted string
            $table->string('service_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 9. Customer Package History (Historical Preserving)
        Schema::create('customer_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connection_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->decimal('actual_price', 10, 2); // Preserves historic price at time of assignment
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active'); // active, expired, upgraded, downgraded
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_packages');
        Schema::dropIfExists('pppoe_credentials');
        Schema::dropIfExists('connections');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customer_contacts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('package_prices');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('areas');
    }
};

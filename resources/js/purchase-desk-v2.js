window.purchaseDeskRetailer = function purchaseDeskRetailer(config = {}) {
    const initialItems = config.items || {};

    return {
        items: initialItems,
        selectedItemId: config.defaultItemId ? String(config.defaultItemId) : '',
        suppliers: Array.isArray(config.suppliers) ? config.suppliers : [],
        newSupplier: '',

        get selectedItem() {
            return this.items[this.selectedItemId] || null;
        },

        selectItem(itemId) {
            this.selectedItemId = String(itemId);
        },

        addSupplier() {
            const supplier = this.newSupplier.trim();

            if (!supplier) {
                return;
            }

            if (!this.suppliers.some((existing) => existing.toLowerCase() === supplier.toLowerCase())) {
                this.suppliers.push(supplier);
                this.suppliers.sort((a, b) => a.localeCompare(b));
            }

            if (this.selectedItem) {
                this.selectedItem.supplier = supplier;
            }

            this.newSupplier = '';
        },

        lineTotal(item) {
            const qty = Math.max(0, Number(item?.qty || 0));
            const price = Math.max(0, Number(item?.price || 0));
            return qty * price;
        },

        basketQty() {
            return this.selectedItem ? Math.max(0, Number(this.selectedItem.qty || 0)) : 0;
        },

        basketTotal() {
            return this.selectedItem ? this.lineTotal(this.selectedItem) : 0;
        },

        money(value) {
            return new Intl.NumberFormat('en-GB', {
                style: 'currency',
                currency: 'GBP',
            }).format(Number(value || 0));
        },
    };
};

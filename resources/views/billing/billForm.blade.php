<!-- Create / Update Bill Modal -->
    <div class="modal fade" id="billFormModal" tabindex="-1" aria-hidden="true" v-show="showBillForm">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form @submit.prevent="saveBill">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ billForm.id ? 'Edit Bill' : 'Create Bill' }}</h5>
                        <button type="button" class="btn-close" @click="closeBillForm"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Reference Number</label>
                                <input type="text" v-model="billForm.bill_number" class="form-control" readonly placeholder="Auto-generated">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client</label>

                                <select
                                    v-model="billForm.client_id"
                                    class="form-select"
                                    required
                                    :disabled="billForm.id !== null"
                                >
                                    <option value="" disabled>Select client</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">@{{ client.name }}</option>
                                </select>

                                <!-- Hidden input to submit client_id when disabled -->
                                <input type="hidden" :value="billForm.client_id" name="client_id" v-if="billForm.id !== null" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Type</label>
                                <select v-model="billForm.type" class="form-select" required>
                                    <option value="" disabled>Select Type</option>
                                    <option value="Receipt">Receipt</option>
                                    <option value="Invoice">Invoice</option>
                                    <option value="Quotation">Quotation</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date</label>
                                <input type="date" v-model="billForm.date" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Due Date</label>
                                <input type="date" v-model="billForm.due_date" class="form-control">
                            </div>

                            <!-- Bill items table -->
                            <div class="col-12">
                                <label class="form-label">Items</label>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                            <th style="width: 40px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in billForm.items" :key="index">
                                            <td><input type="text" v-model="item.description" class="form-control" required></td>
                                            <td><input type="number" v-model.number="item.quantity" min="1" class="form-control" required></td>
                                            <td><input type="number" v-model.number="item.unit_price" step="0.01" min="0" class="form-control" required></td>
                                            <td>@{{ (item.quantity * item.unit_price).toFixed(2) }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger" @click="removeItem(index)" :disabled="billForm.items.length === 1">×</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="addItem">+ Add Item</button>
                            </div>

                            <div class="col-md-6 mt-3">
                                <label class="form-label">Notes</label>
                                <textarea v-model="billForm.notes" rows="3" class="form-control"></textarea>
                            </div>

                            <div class="col-md-6 mt-3 text-end">
                                <h5>Total: K@{{ billTotal.toFixed(2) }}</h5>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeBillForm">Cancel</button>
                        <button type="submit" class="btn btn-primary">@{{ billForm.id ? 'Update' : 'Create' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
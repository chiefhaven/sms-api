<!-- Create / Update Client Modal -->
    <div class="modal fade" id="clientFormModal" tabindex="-1" aria-hidden="true" v-show="showClientForm">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form @submit.prevent="saveClient">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ clientForm.id ? 'Edit Client' : 'Add Client' }}</h5>
                        <button type="button" class="btn-close" @click="closeClientForm"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" v-model="clientForm.name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company</label>
                                <input type="text" v-model="clientForm.company" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sender ID</label>
                                <input type="text" v-model="clientForm.sender_id" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Balance</label>
                                <input type="number" step="0.01" v-model="clientForm.account_balance" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select v-model="clientForm.status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input
                                  type="email"
                                  v-model="clientForm.email"
                                  class="form-control"
                                  :disabled="clientForm.id !== null"
                                >
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" v-model="clientForm.phone" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeClientForm">Cancel</button>
                        <button type="submit" class="btn btn-primary">@{{ clientForm.id ? 'Update' : 'Create' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
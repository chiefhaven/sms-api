@extends('layouts.app')

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Clients'])
    <div id="clients">
        <div class="row mt-4 mx-4">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6>Clients</h6>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="reloadTable('active')">Active</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="reloadTable('inactive')">Inactive</button>
                                <button type="button" class="btn btn-sm btn-outline-warning" @click="reloadTable('pending')">Pending</button>
                                <button type="button" class="btn btn-sm btn-outline-dark" @click="reloadTable('')">All</button>
                            </div>
                            <button type="button" class="btn btn-sm btn-success" @click="openClientForm()">+ Add Client</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pt-4">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0" id="clientsTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Company</th>
                                        <th>Sender ID</th>
                                        <th>Account Balance</th>
                                        <th>Status</th>
                                        <th>Email</th>
                                        <th>Tokens</th>
                                        <th>Phone</th>
                                        <th class="text-center">Create Date</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data loaded via DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('clients.clientForm')

        <!-- Status Change Modal -->
        <div class="modal fade" id="statusChangeModal" tabindex="-1" aria-hidden="true" v-if="showStatusChangeModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Change Status for @{{ clientName }}</h5>
                        <button type="button" class="btn-close" @click="closeStatusChangeModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Status</label>
                            <input type="text" class="form-control" :value="clientStatus" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select class="form-select" v-model="clientStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="mb-3" v-if="clientStatus === 'inactive'">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" v-model="completionNotes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeStatusChangeModal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="confirmChangeStatus">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const clients = createApp({
            setup() {
                const status = ref('active');
                const showStatusChangeModal = ref(false);
                const showClientForm = ref(false);
                const clientId = ref(null);
                const clientName = ref('');
                const clientStatus = ref('');
                const completionNotes = ref('');
                const isLoading = ref(false);
                const clientForm = reactive({
                    id: null,
                    name: '',
                    company: '',
                    sender_id: '',
                    account_balance: '',
                    status: 'active',
                    email: '',
                    phone: ''
                });

                onMounted(() => {
                    initializeDataTable();
                });

                const initializeDataTable = () => {
                    if ($.fn.DataTable.isDataTable('#clientsTable')) {
                        $('#clientsTable').DataTable().destroy();
                    }

                    $('#clientsTable').DataTable({
                        serverSide: true,
                        processing: true,
                        responsive: true,
                        autoWidth: false,
                        scrollX: true,
                        ajax: {
                            url: '/api/clients',
                            data: d => { d.status = status.value; return d; },
                            beforeSend: () => isLoading.value = true,
                            complete: () => isLoading.value = false
                        },
                        columns: [
                            { data: 'name' },
                            { data: 'company' },
                            { data: 'sender_id' },
                            { data: 'account_balance', render: data => `<strong>K${parseFloat(data).toFixed(2)}</strong>`, className: 'text-end' },
                            { data: 'status', render: data => renderStatusBadge(data), className: 'text-center' },
                            { data: 'email' },
                            { data: 'tokens' },
                            { data: 'phone' },
                            { data: 'created_at', render: data => data ? new Date(data).toLocaleDateString() : 'N/A', className: 'text-center' },
                            { data: null, render: renderActions, orderable: false, className: 'text-center' }
                        ],
                        createdRow: (row, data) => $(row).attr('data-id', data.id),
                        drawCallback: bindEventHandlers
                    });
                };

                const renderStatusBadge = (status) => {
                    let badgeClass = 'badge bg-';
                    if (status === 'active') badgeClass += 'success';
                    else if (status === 'inactive') badgeClass += 'secondary';
                    else if (status === 'pending') badgeClass += 'warning text-dark';
                    else badgeClass += 'dark';
                    return `<span class="${badgeClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                };

                const renderActions = (data, type, row) => `
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">Actions</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item edit-client-btn" data-client='${JSON.stringify(row)}'>Edit</button></li>
                            <li><button class="dropdown-item change-status-btn" data-id="${row.id}" data-status="${row.status}" data-name="${row.name}">Change Status</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="/api/clients/${row.id}" class="delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger delete-confirm">Delete</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                `;

                const bindEventHandlers = () => {
                    $('.change-status-btn').off('click').on('click', function() {
                        openStatusChangeModal($(this).data('id'), $(this).data('status'), $(this).data('name'));
                    });
                    $('.edit-client-btn').off('click').on('click', function() {
                        let data = $(this).data('client');
                        openClientForm(data);
                    });
                    $('.delete-confirm').off('click').on('click', function(e) {
                        e.preventDefault();
                        const form = $(this).closest('form');
                        Swal.fire({
                            title: 'Delete Client',
                            text: 'Are you sure you want to delete this client?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Delete'
                        }).then(result => { if (result.isConfirmed) form.submit(); });
                    });
                };

                const reloadTable = (val) => {
                    status.value = val;
                    $('#clientsTable').DataTable().ajax.reload();
                };

                const openStatusChangeModal = (id, status, name) => {
                    clientId.value = id;
                    clientName.value = name;
                    clientStatus.value = status;
                    completionNotes.value = '';
                    showStatusChangeModal.value = true;
                    new bootstrap.Modal(document.getElementById('statusChangeModal')).show();
                };

                const closeStatusChangeModal = () => {
                    showStatusChangeModal.value = false;
                    bootstrap.Modal.getInstance(document.getElementById('statusChangeModal')).hide();
                };

                const openClientForm = (data = null) => {
                    if (data) Object.assign(clientForm, data);
                    else Object.assign(clientForm, { id: null, name: '', company: '', sender_id: '', account_balance: '', status: 'active', email: '', phone: '' });
                    showClientForm.value = true;
                    new bootstrap.Modal(document.getElementById('clientFormModal')).show();
                };

                const closeClientForm = () => {
                    showClientForm.value = false;
                    bootstrap.Modal.getInstance(document.getElementById('clientFormModal')).hide();
                };

                const saveClient = async () => {
                    try {
                        const url = clientForm.id ? `/api/clients/${clientForm.id}` : '/api/clients';
                        const method = clientForm.id ? 'put' : 'post';
                        await axios[method](url, clientForm);
                        Swal.fire('Success', `Client ${clientForm.id ? 'updated' : 'created'} successfully.`, 'success');
                        closeClientForm();
                        reloadTable();
                    } catch (error) {
                        Swal.fire('Error', error.response?.data?.message || 'Failed to save client', 'error');
                    }
                };

                const confirmChangeStatus = async () => {
                    const result = await Swal.fire({
                        title: 'Confirm Status Change',
                        text: `Are you sure you want to change the status to ${clientStatus.value}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Confirm'
                    });
                    if (result.isConfirmed) await saveStatusChange();
                };

                const saveStatusChange = async () => {
                    try {
                        await axios.post(`/api/updateClientStatus/${clientId.value}`, { status: clientStatus.value, completion_notes: completionNotes.value });
                        Swal.fire('Success', 'Client status updated successfully.', 'success');
                        closeStatusChangeModal();
                        reloadTable();
                    } catch (error) {
                        Swal.fire('Error', error.response?.data?.message || 'Failed to update status', 'error');
                    }
                };

                return {
                    status, showStatusChangeModal, showClientForm,
                    clientId, clientName, clientStatus, completionNotes, isLoading,
                    clientForm, reloadTable, openStatusChangeModal, closeStatusChangeModal,
                    confirmChangeStatus, openClientForm, closeClientForm, saveClient
                };
            }
        });

        clients.mount('#clients');
    </script>
@endsection

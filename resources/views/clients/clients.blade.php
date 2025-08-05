@extends('layouts.app')

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Clients'])
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
                    </div>
                </div>
                <div class="card-body px-4 pt-4" id="clients">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" id="clientsTable">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Company</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Sender ID</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Account Balance</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Email</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Phone</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Create Date</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <script>

        const clients = createApp({
            setup() {
                const status = ref('active');
                const showStatusChangeModal = ref(false);
                const clientId = ref(null);
                const clientName = ref('');
                const clientStatus = ref('');
                const completionNotes = ref('');
                const isLoading = ref(false);

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
                            data: function(d) {
                                d.status = status.value;
                                return d;
                            },
                            beforeSend: () => isLoading.value = true,
                            complete: () => isLoading.value = false
                        },
                        columns: [
                            { data: 'name' },
                            { data: 'company' },
                            { data: 'sender_id' },
                            {
                                data: 'account_balance',
                                className: 'text-end',
                                render: function(data) {
                                    return `<div class="text-end"><strong>K${parseFloat(data).toFixed(2)}</strong></div>`;
                                }
                            },
                            {
                                data: 'status',
                                className: 'text-center',
                                render: function(data) {
                                    let badgeClass = 'badge bg-';
                                    switch(data) {
                                        case 'active': badgeClass += 'success'; break;
                                        case 'inactive': badgeClass += 'secondary'; break;
                                        case 'pending': badgeClass += 'warning text-dark'; break;
                                        default: badgeClass += 'dark';
                                    }
                                    return `<span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                                }
                            },
                            { data: 'email' },
                            { data: 'phone' },
                            {
                                data: 'create_date',
                                className: 'text-center',
                                render: function(data) {
                                    return data ? new Date(data).toLocaleDateString() : 'N/A';
                                }
                            },
                            {
                                data: 'actions',
                                className: 'text-center',
                                orderable: false,
                                render: function(data, type, row) {
                                    return `
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="/clients/${row.id}/edit">Edit</a></li>
                                                <li><button class="dropdown-item change-status-btn" data-id="${row.id}" data-status="${row.status}" data-name="${row.name}">Change Status</button></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="/clients/${row.id}" class="delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger delete-confirm">Delete</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    `;
                                }
                            }
                        ],
                        createdRow: function(row, data) {
                            $(row).attr('data-id', data.id);
                        },
                        drawCallback: function() {
                            bindEventHandlers();
                        }
                    });
                };

                const bindEventHandlers = () => {
                    $('.change-status-btn').off('click').on('click', function() {
                        const id = $(this).data('id');
                        const status = $(this).data('status');
                        const name = $(this).data('name');
                        openStatusChangeModal(id, status, name);
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
                            confirmButtonText: 'Delete',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
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

                const saveStatusChange = async () => {
                    NProgress.start();
                    try {
                        const response = await axios.post(`/updateClientStatus/${clientId.value}`, {
                            status: clientStatus.value,
                            completion_notes: completionNotes.value
                        });

                        showAlert('Success', 'Client status updated successfully.', { icon: 'success' });
                        closeStatusChangeModal();
                        reloadTable();
                    } catch (error) {
                        showError('Error', error.response?.data?.message || 'Failed to update status');
                    } finally {
                        NProgress.done();
                    }
                };

                const confirmChangeStatus = async () => {
                    const result = await Swal.fire({
                        title: 'Confirm Status Change',
                        text: `Are you sure you want to change the status to ${clientStatus.value}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Confirm',
                        cancelButtonText: 'Cancel'
                    });

                    if (result.isConfirmed) {
                        await saveStatusChange();
                    }
                };

                const showAlert = (title, text, options = {}) => {
                    Swal.fire({
                        title,
                        text,
                        icon: options.icon || 'success',
                        toast: options.toast || false,
                        timer: options.timer || 3000,
                        position: options.position || 'top-end'
                    });
                };

                const showError = (title, text) => {
                    Swal.fire({
                        title,
                        text,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                };

                return {
                    status,
                    showStatusChangeModal,
                    clientId,
                    clientName,
                    clientStatus,
                    completionNotes,
                    isLoading,
                    reloadTable,
                    openStatusChangeModal,
                    closeStatusChangeModal,
                    confirmChangeStatus
                };
            }
        });

        clients.mount('#clients');
    </script>
@endsection
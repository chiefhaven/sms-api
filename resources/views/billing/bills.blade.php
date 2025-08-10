@extends('layouts.app')

@section('content')
@include('layouts.navbars.auth.topnav', ['title' => 'Billing'])

<div id="billing" class="container-fluid py-4">
    <div class="row mt-4 mx-4">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6>Bills</h6>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="reloadTable('active')">Active</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="reloadTable('inactive')">Paid</button>
                            <button type="button" class="btn btn-sm btn-outline-warning" @click="reloadTable('pending')">Partial</button>
                            <button type="button" class="btn btn-sm btn-outline-dark" @click="reloadTable('')">All</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" @click="openBillForm()">+ Add Bill</button>
                    </div>
                </div>

                <div class="card-body px-4 pt-4">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" id="billingTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Reference Number</th>
                                    <th>Client Name</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th class="text-center">Date</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody><!-- Data loaded via DataTables --></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('billing.billForm')
</div>

<script>
    const billing = createApp({
        setup() {
            const showBillForm = ref(false);
            const billId = ref(null);
            const billReference = ref('');
            const completionNotes = ref('');
            const isLoading = ref(false);
            const clients = ref([]);

            // Bill form reactive object
            const billForm = reactive({
                id: null,
                bill_number: '',
                client_id: '',
                type: '',
                date: '',
                due_date: '',
                items: [{ description: '', quantity: 1, unit_price: 0 }],
                notes: '',
                status: 'active',
            });

            // Compute total amount
            const billTotal = computed(() => {
                return billForm.items.reduce((sum, item) => {
                    const qty = parseFloat(item.quantity) || 0;
                    const price = parseFloat(item.unit_price) || 0;
                    return sum + qty * price;
                }, 0);
            });

            // Fetch bills for dropdown on mount
            onMounted(() => {
                initializeDataTable();
                fetchClients();
            });

            const fetchClients = async () => {
                try {
                    const response = await axios.get('/api/clients');
                    clients.value = response.data.data || response.data;
                } catch (error) {
                    console.error('Failed to fetch clients:', error);
                }
            };

            const initializeDataTable = () => {
                if ($.fn.DataTable.isDataTable('#billingTable')) {
                    $('#billingTable').DataTable().destroy();
                }

                $('#billingTable').DataTable({
                    serverSide: true,
                    processing: true,
                    responsive: true,
                    autoWidth: false,
                    scrollX: true,
                    ajax: {
                        url: '/api/bills',
                        data: d => {
                            d.status = status.value;
                            return d;
                        },
                        beforeSend: () => isLoading.value = true,
                        complete: () => isLoading.value = false
                    },
                    columns: [
                        { data: 'bill_number' },
                        { data: 'client.name', defaultContent: '', orderable: false },
                        { data: 'type' },
                        { data: 'total_amount', render: data => `<strong>K${parseFloat(data).toFixed(2)}</strong>`, className: 'text-end' },
                        { data: 'status', render: data => renderStatusBadge(data), className: 'text-center' },
                        { data: 'date', render: data => data ? new Date(data).toLocaleDateString() : 'N/A', className: 'text-center' },
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
                        <li><button class="dropdown-item edit-bill-btn" data-bill='${JSON.stringify(row)}'>Edit</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="/api/billing/${row.id}" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger delete-confirm">Delete</button>
                            </form>
                        </li>
                    </ul>
                </div>
            `;

            const bindEventHandlers = () => {

                $('.edit-bill-btn').off('click').on('click', function() {
                    let data = $(this).data('bill');
                    openBillForm(data);
                });

                $('.delete-confirm').off('click').on('click', function(e) {
                    e.preventDefault();
                    const form = $(this).closest('form');
                    Swal.fire({
                        title: 'Delete Bill/Invoice/Quotation',
                        text: 'Are you sure you want to delete this record?',
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
                $('#billingTable').DataTable().ajax.reload();
            };

            const openBillForm = (data = null) => {
                if (data) {
                    // Make sure to fill billForm with data and reset items if missing
                    Object.assign(billForm, {
                        id: data.id,
                        bill_number: data.bill_number || '',
                        client_id: data.client?.id || '',
                        type: data.type || '',
                        date: data.date || '',
                        due_date: data.due_date || '',
                        items: data.items && data.items.length ? data.items : [{ description: '', quantity: 1, unit_price: 0 }],
                        notes: data.notes || '',
                        status: data.status || 'active',
                    });
                } else {
                    Object.assign(billForm, {
                        id: null,
                        bill_number: '',
                        client_id: '',
                        type: '',
                        date: '',
                        due_date: '',
                        items: [{ description: '', quantity: 1, unit_price: 0 }],
                        notes: '',
                        status: 'active',
                    });
                }
                showBillForm.value = true;
                new bootstrap.Modal(document.getElementById('billFormModal')).show();
            };

            const resetBillForm = () => {
                Object.assign(billForm, {
                    id: null,
                    bill_number: '',
                    client_id: '',
                    type: '',
                    date: '',
                    due_date: '',
                    items: [{ description: '', quantity: 1, unit_price: 0 }],
                    notes: '',
                    status: 'active',
                });
            };

            const closeBillForm = () => {
                resetBillForm();
                showBillForm.value = false;
                bootstrap.Modal.getInstance(document.getElementById('billFormModal')).hide();
            };

            const addItem = () => {
                billForm.items.push({ description: '', quantity: 1, unit_price: 0 });
            };

            const removeItem = (index) => {
                if (billForm.items.length > 1) {
                    billForm.items.splice(index, 1);
                }
            };

            const saveBill = async () => {
                try {
                    const url = billForm.id ? `/api/billing/${billForm.id}` : '/api/billing';
                    const method = billForm.id ? 'put' : 'post';

                    // Prepare payload: calculate total amount
                    const payload = {
                        client_id: billForm.client_id,
                        bill_number: billForm.bill_number,
                        type: billForm.type,
                        date: billForm.date,
                        due_date: billForm.due_date,
                        items: billForm.items,
                        notes: billForm.notes,
                        status: billForm.status,
                        total_amount: billTotal.value
                    };

                    await axios[method](url, payload);
                    Swal.fire('Success', `Record ${billForm.id ? 'updated' : 'created'} successfully.`, 'success');
                    closeBillForm();
                    reloadTable();
                } catch (error) {
                    Swal.fire('Error', error.response?.data?.message || 'Failed to save record', 'error');
                    console.log('Save Bill Error:', error.response?.data?.errors || error)
                }
            };

            return {
                status, showBillForm,
                billId, billReference,  completionNotes, isLoading,
                billForm, clients, billTotal,
                reloadTable, openBillForm, closeBillForm,
                addItem, removeItem, saveBill
            };
        }
    });

    billing.mount('#billing');
</script>
@endsection

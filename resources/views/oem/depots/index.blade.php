@section('title')
    OEM Depots
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>OEM Depots</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('oems.index') }}">OEM</a></li>
                    <li class="breadcrumb-item active">Manage Depot</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec mb-3">Basic Details</h5>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-barcode"></i>
        </div>
        <div class="oem-widget-content">
            <span>OEM Code</span>
            <strong>{{ $oem->oem_code }}</strong>
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-building"></i>
        </div>
        <div class="oem-widget-content">
            <span>OEM Name</span>
            <strong>{{ $oem->oem_name }}</strong>
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-layer-group"></i>
        </div>
        <div class="oem-widget-content">
            <span>Type</span>
            <strong>{{ $oem->oem_type ?? '-' }}</strong>
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-location-dot"></i>
        </div>
        <div class="oem-widget-content">
            <span>State</span>
            <strong>{{ $oem->state?->name ?? '-' }}</strong>
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div class="oem-widget-content">
            <span>GST Number</span>
            <strong>{{ $oem->gst_number ?: '-' }}</strong>
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6 mb-3">
    <div class="oem-depo-widget">
        <div class="oem-widget-icon">
            <i class="fa-solid fa-user-tie"></i>
        </div>
        <div class="oem-widget-content">
            <span>Primary Contact</span>
            <strong>{{ $oem->primaryContact?->contact_person ?? '-' }}</strong>
        </div>
    </div>
</div>
            </div>
        </div>

        @can('oems.edit')
            <div class="modal fade" id="oemDepotModal" tabindex="-1" aria-labelledby="oemDepotLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="oemDepotLabel">Add Depot</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('oems.depots.store', $oem->id) }}" method="POST" id="oemDepotForm">
                                @csrf
                                <input type="hidden" id="formMethod" name="_method" value="POST" disabled>
                                <div class="row">
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="depotId">Depot <span class="text-danger">*</span></label>
                                        <select name="depot_id" id="depotId" class="form-select shadow-none" required>
                                            <option value="">--- Select ---</option>
                                            @foreach ($depots as $depot)
                                                <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="branchLocationId">Branch <span class="text-danger">*</span></label>
                                        <select name="branch_location_id" id="branchLocationId" class="form-select shadow-none" required disabled>
                                            <option value="">--- Select ---</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6 o-f-inp mb-3">
                                        <label for="depotStatus">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="depotStatus" class="form-select shadow-none" required>
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-12 ">
                                        <div class="modal-btns-last">
                                            <button type="button" class="modal-btn-1" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="modal-btn-2">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        <div class="main-table-container">
            <div class="row mb-3">
                <div class="col-lg-12 d-flex justify-content-end align-items-end">
                    <div class="btns-group-container">
                        <a href="{{ route('oems.index') }}" class="btn-back-cs"><i class="fa-solid fa-arrow-left"></i> Back</a>
                        @can('oems.edit')
                            <a class="add-btn" data-bs-toggle="modal" href="#oemDepotModal" role="button" id="addOemDepot">
                                Add Depot
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="table-over">
                <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center nowrap">SL NO</th>
                            <th class="text-center nowrap">Depot</th>
                            <th class="text-center nowrap">Branch</th>
                            <th class="text-center nowrap">Status</th>
                            <th class="text-center nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

    @section('scripts')
        <style>
            .oem-detail-card {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 14px 16px;
                min-height: 78px;
                background: #fff;
            }

            .oem-detail-card span {
                display: block;
                color: #6b7280;
                font-size: 13px;
                margin-bottom: 8px;
            }

            .oem-detail-card strong {
                display: block;
                color: #111827;
                font-size: 15px;
                font-weight: 600;
                word-break: break-word;
            }
        </style>
        <script>
            $(function () {
                var createUrl = "{{ route('oems.depots.store', $oem->id) }}";

                $('#depotId, #branchLocationId, #depotStatus').select2({
                    placeholder: '--- Select ---',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#oemDepotModal')
                });

                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('oems.depots.index', $oem->id) }}"
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'depot', name: 'depot.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'branch', name: 'branchLocation.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[0, 'desc']]
                });

                $('#addOemDepot').on('click', resetForm);

                $('#depotId').on('change', function () {
                    loadBranches($(this).val(), []);
                });

                $(document).on('click', '.edit-oem-depot', function () {
                    var depot = $(this).data('depot');
                    $('#oemDepotLabel').text('Edit Depot');
                    $('#oemDepotForm').attr('action', '/oem-depots/' + depot.id);
                    $('#formMethod').prop('disabled', false).val('PUT');
                    $('#depotStatus').val(depot.status ? '1' : '0').trigger('change');
                    $('#depotId').val(depot.depot_id).trigger('change.select2');
                    loadBranches(depot.depot_id, depot.branch_location_id);
                    $('#oemDepotModal').modal('show');
                });

                $('#oemDepotModal').on('hidden.bs.modal', resetForm);

                $('#oemDepotForm').on('submit', function (e) {
                    e.preventDefault();

                    var form = $(this);
                    var button = form.find('button[type="submit"]');
                    var originalText = button.data('original-text') || button.text();
                    button.data('original-text', originalText).prop('disabled', true).text('Please wait...');

                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        success: function (res) {
                            $('#oemDepotModal').modal('hide');
                            table.ajax.reload();
                            showToast('success', res.message);
                        },
                        error: function (xhr) {
                            let message = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                                message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            }
                            showToast('error', message);
                        },
                        complete: function () {
                            button.prop('disabled', false).text(originalText);
                        }
                    });
                });

                $(document).on('change', '.toggleDepotStatus', function () {
                    var checkbox = $(this);
                    var id = checkbox.data('id');
                    var status = checkbox.is(':checked') ? 1 : 0;

                    $.ajax({
                        url: '/oem-depots/' + id + '/status',
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            status: status
                        },
                        success: function (res) {
                            table.ajax.reload();
                            showToast('success', res.message);
                        },
                        error: function (xhr) {
                            table.ajax.reload();
                            showToast('error', xhr.responseJSON?.message || 'An error occurred. Please try again.');
                        }
                    });
                });

                function loadBranches(depotId, selectedId) {
                    var branchSelect = $('#branchLocationId');
                    branchSelect.empty().prop('disabled', true).trigger('change.select2');
                    branchSelect.append(new Option('--- Select ---', ''));

                    if (!depotId) {
                        return;
                    }

                    $.ajax({
                        url: '/oem-depots/depots/' + depotId + '/branches',
                        type: 'GET',
                        success: function (branches) {
                            $.each(branches, function (index, branch) {
                                branchSelect.append(new Option(branch.name, branch.id));
                            });

                            branchSelect.prop('disabled', branches.length === 0)
                                .val(selectedId || '')
                                .trigger('change.select2');
                        },
                        error: function () {
                            showToast('error', 'Failed to load branches.');
                        }
                    });
                }

                function resetForm() {
                    $('#oemDepotLabel').text('Add Depot');
                    $('#oemDepotForm').attr('action', createUrl)[0].reset();
                    $('#formMethod').prop('disabled', true).val('POST');
                    $('#depotId').val('').trigger('change');
                    $('#branchLocationId').empty().append(new Option('--- Select ---', '')).prop('disabled', true).trigger('change.select2');
                    $('#depotStatus').val('1').trigger('change');
                }
            });

            function deleteOemDepot(id) {
                deleteRecord('/oem-depots/' + id, 'table', 'Do you really want to delete this depot mapping?');
            }
        </script>
    @endsection
</x-app-layout>

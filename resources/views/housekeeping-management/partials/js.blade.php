<script>
$(function () {
    $('#stateFilter').select2({ placeholder: '---Select---', allowClear: true, width: '100%' });
    if ($('#expiryFilter').val()) $('#filterCollapse').collapse('show');
    const table = $('#table').DataTable({
        processing: true, serverSide: true, searching: false,
        ajax: { url: "{{ route('housekeeping-management.index') }}", data: filters },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false, className: 'text-center', render: (data,type,row) => `<input type="checkbox" class="row-check" value="${row.id}">` },
            { data: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'code', className: 'text-center' }, { data: 'name', className: 'text-center' },
            { data: 'phone_number', orderable: false, className: 'text-center' },
            { data: 'employment_type', orderable: false, className: 'text-center' },
            { data: 'depot', orderable: false, className: 'text-center' },
            { data: 'verification_status', orderable: false, className: 'text-center' },
            { data: 'status', orderable: false, className: 'text-center' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[3, 'asc']]
    });
    let timer; $('#searchFilter').on('keyup', () => { clearTimeout(timer); timer = setTimeout(() => table.ajax.reload(), 300); });
    $('#searchFilters').click(() => table.ajax.reload());
    $('#resetFilters').click(function(){ $('#stateFilter,#employmentTypeFilter,#verificationStatusFilter,#statusFilter,#expiryFilter').val(''); $('#stateFilter').trigger('change.select2'); $('#searchFilter').val(''); table.ajax.reload(); });
    $('#checkAll').change(function(){ $('.row-check').prop('checked', this.checked); });
    $(document).on('click','.toggleStatus',function(){ $.post("{{ route('housekeeping-management.status') }}", {_token:"{{ csrf_token() }}",id:$(this).data('id'),status:$(this).data('status')?0:1}, () => table.ajax.reload()); });
    $('#exportSelected').click(function(){ const ids=$('.row-checkbox:checked').map((i,e)=>e.value).get(); if(!ids.length) return showToast('warning','Please select at least one row to export.'); $.ajax({url:"{{ route('housekeeping-management.export') }}",type:'POST',data:{_token:"{{ csrf_token() }}",ids},xhrFields:{responseType:'blob'},success:data=>{const url=URL.createObjectURL(new Blob([data]));const a=document.createElement('a');a.href=url;a.download='housekeeping-management.xlsx';a.click();URL.revokeObjectURL(url);}}); });
    function filters(d){ d.search_text=$('#searchFilter').val();d.state_id=$('#stateFilter').val();d.employment_type=$('#employmentTypeFilter').val();d.verification_status=$('#verificationStatusFilter').val();d.status=$('#statusFilter').val();d.expiry_filter=$('#expiryFilter').val(); }
    window.deleteRow=id=>deleteRecord('/housekeeping-management/'+id,'table','Do you really want to delete this housekeeping user?');
});
</script>

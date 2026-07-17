<script>
$(function(){
    $('#entityFilter,#languageFilter,#statusFilter').select2({width:'100%',allowClear:true});
    var table=$('#table').DataTable({processing:true,serverSide:true,ajax:{url:"{{ route('hr-letter-templates.index') }}",data:function(data){data.entity_type=$('#entityFilter').val();data.language=$('#languageFilter').val();data.status=$('#statusFilter').val();}},columns:[
        {data:'DT_RowIndex',name:'DT_RowIndex',orderable:false,searchable:false,className:'text-center'},
        {data:'entity_label',name:'entity_type',className:'text-center'},
        {data:'template_name',name:'template_name',className:'text-center'},
        {data:'language',name:'language',className:'text-center'},
        {data:'status_label',name:'is_active',orderable:false,searchable:false,className:'text-center'},
        {data:'action',name:'action',orderable:false,searchable:false,className:'text-center'}
    ]});
    $('#entityFilter,#languageFilter,#statusFilter').on('change',function(){table.ajax.reload();});
    $('#resetFilters').on('click',function(){$('#entityFilter,#languageFilter,#statusFilter').val('').trigger('change.select2');table.ajax.reload();});
});
function deleteRow(id){deleteRecord('/hr-letter-templates/'+id,'table','Do you really want to delete this letter template?');}
</script>

@php($slip = $slip ?? \App\Support\PaySlipData::build($processing, $item))
@include('payroll.pay-slip.partials.styles')
@include('payroll.pay-slip.partials.document', ['slip' => $slip])

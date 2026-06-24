<!DOCTYPE html>
<html>
<body>
    <p>Dear Team,</p>
    <p>Please find attached the generated salary report.</p>
    <p>
        <strong>Period:</strong> {{ $period }}<br>
        <strong>Depo:</strong> {{ $depot }}<br>
        <strong>Role:</strong> {{ $role }}
    </p>
    <p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>

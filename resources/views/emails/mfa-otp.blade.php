<x-mail::message>
# Verification Code

Hi **{{ $userName }}**,

Use the code below to complete your sign-in to **Security Assessment**. This code expires in **10 minutes**.

<x-mail::panel>
<div style="text-align:center;font-size:2rem;font-weight:800;letter-spacing:.5rem;color:#222">{{ $otp }}</div>
</x-mail::panel>

If you did not attempt to sign in, please ignore this email or contact your administrator immediately.

Thanks,<br>
Security Assessment Team
</x-mail::message>

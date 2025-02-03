<x-mail::message>
Dear {{ $restaurant->name }},

Welcome to Moboeats! We’re excited to have you join our growing network of food and hospitality businesses. Your partnership allows us to bring great meals and products to more customers conveniently, and we’re here to support you every step of the way.

Next Steps to Get Started:

✅ Log in to Your Partner Portal – {{ config('app.partner_dashboard_url') }}. <br/>
✅ Update Your Menu & Prices – Ensure your offerings are accurate and appealing to customers. <br/>
✅ Verify Your Business Details – Check that your contact info, operating hours, and delivery options are correct. <br/>
✅ Start Receiving Orders! – Once everything is set, you’re ready to serve customers on Moboeats. <br/>

Need assistance? Our support team is here to help! Feel free to reach out to us at admin@moboeats.co.uk

We’re looking forward to a successful partnership. Let’s grow together!

Best Regards,<br>
{{ config('app.name') }} Team <br>
Website: moboeats.com. <br>
Tel: +254 759 173 592
</x-mail::message>

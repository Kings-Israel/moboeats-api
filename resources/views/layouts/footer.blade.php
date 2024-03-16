<div class="bg-primary-one px-2 lg:px-24 mt-2 pb-12 lg:flex lg:gap-12">
    <img src="{{ asset('assets/img/favicon/android-chrome-192x192.png') }}" class="w-[10rem] h-[10rem] object-contain" alt="">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 lg:gap-32 py-8 text-white">
        <div class="text-center md:text-left">
            <ul class="space-y-4">
                <li>
                    <a href="#" class="font-bold hover:text-primary-two">
                        Install our App
                    </a>
                </li>
                <li><a href="https://restaurant.moboeats.com/signup" class="font-bold hover:text-primary-two">Register your Restaurant</a></li>
                <li><a href="https://restaurant.moboeats.com/signup" class="font-bold hover:text-primary-two">Register Your Shop</a></li>
                <li><a href="#" class="font-bold hover:text-primary-two">Be Our Driver</a></li>
            </ul>
        </div>
        <div class="text-center md:text-left">
            <ul class="py-2 flex gap-4">
                <li class="px-0.5">
                    <a href="#" class="">
                        <img src="{{ asset('assets/img/instagram.png') }}" alt="" class="w-9 h-9">
                    </a>
                </li>
                <li class="-mt-1">
                    <a href="#" class="">
                        <img src="{{ asset('assets/img/linkedin.png') }}" alt="" class="w-11 h-11">
                    </a>
                </li>
                <li class="px-1">
                    <a href="#" class="">
                        <img src="{{ asset('assets/img/facebook.png') }}" alt="" class="w-9 h-9">
                    </a>
                </li>
                <li class="">
                    <a href="#" class="">
                        <img src="{{ asset('assets/img/threads-logo.png') }}" alt="" class="w-9 h-9">
                    </a>
                </li>
            </ul>
        </div>
        <div class="text-center md:text-left">
            <ul>
                <li><button class="font-bold hover:text-primary-two" data-modal-target="customer-terms-and-conditions" data-modal-toggle="customer-terms-and-conditions">Customer Terms and Conditions</button></li>
            </ul>
        </div>

        <!-- Main modal -->
        <div id="customer-terms-and-conditions" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-4xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-primary-one rounded-lg shadow">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-white">
                            Terms and Conditions
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="customer-terms-and-conditions">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <span class="font-bold">
                            Welcome to Mobo Eats! Please carefully read and understand the following terms and
                            conditions governing the use of our food, grocery delivery and dine in app ("the App") before
                            using it:
                        </span>
                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Acceptance of Terms:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    By downloading, accessing, or using the App, you agree to be
                                    bound by these terms and conditions. If you do not agree with any part of these terms,
                                    you may not use the App.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    User Accounts:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    To use certain features of the App, you may be required to create a user
                                    account. You are responsible for maintaining the confidentiality of your account
                                    information and for all activities that occur under your account.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Service Description:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The App allows users to place orders for food & grocery items
                                    from local restaurants, stores, or vendors ("Merchants"), book a dine in and order your
                                    food before you get to the restaurant as well as facilitates delivery by independent
                                    couriers.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Ordering and Payments:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    By placing an order through the App, you agree to pay the total
                                    amount due for the order, including the cost of items, delivery fees, and applicable
                                    taxes. Payments are processed securely through the App's payment gateway.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Delivery Services:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The App connects users with independent couriers who fulfill
                                    delivery requests. The delivery time estimates provided in the App are approximate and
                                    may vary based on factors such as traffic conditions and order volume.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    User Responsibilities:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    As a user of the App, you agree to:
                                </p>
                                <ul class="list-desc">
                                    <li>Provide accurate and complete information when placing orders.</li>
                                    <li>Abide by the App's terms and conditions, including payment obligations.</li>
                                    <li>Treat couriers and merchants with respect and courtesy during interactions.</li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Merchant Relationships:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The App acts as a platform to facilitate transactions between
                                    users and merchants. We are not responsible for the quality, accuracy, or timeliness of
                                    products or services provided by merchants.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Delivery Couriers:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The couriers providing delivery services through the App are
                                    independent contractors. While we strive to ensure a positive experience, we are not
                                    liable for actions or omissions of couriers during deliveries.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Intellectual Property:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    All content, logos, and trademarks displayed in the App are the
                                    property of Mobo Eats Ltd or its licensors. Users may not reproduce, distribute, or
                                    modify any content from the App without prior authorization.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Privacy Policy:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    Our privacy policy outlines how we collect, use, and protect your
                                    personal information. By using the App, you consent to the collection and processing of
                                    your data as described in the privacy policy.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Updates and Changes:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    We reserve the right to update, modify, or discontinue the App
                                    or its features at any time without prior notice. Users will be informed of significant
                                    changes to the App's functionality or terms.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Limitation of Liability:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    In no event shall Mobo Eats or its affiliates be liable for any
                                    indirect, incidental, special, or consequential damages arising out of or in connection
                                    with the use of the App.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Governing Law:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    These terms and conditions shall be governed by and construed in
                                    accordance with the laws of United Kingdom. Any disputes arising from the use of the
                                    App shall be subject to the exclusive jurisdiction of the courts in the United Kingdom.
                                </p>
                            </li>
                        </ul>
                        <span class="font-bold mt-6">
                            By using the App, you acknowledge that you have read, understood, and agreed to abide by
                            these terms and conditions. If you have any questions or concerns, please contact us at
                            <a href="mailto:info@moboeats.co.uk." class="text-primary-one underline">info@moboeats.co.uk.</a>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="bg-primary-one px-2 lg:px-24 mt-2 pb-12 lg:flex lg:gap-12">
    <div class="flex justify-center lg:justify-start">
        <img src="{{ asset('assets/img/favicon/android-chrome-192x192.png') }}" class="w-[10rem] h-[10rem] object-contain" alt="">
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 lg:gap-32 py-8 text-white">
        <div class="text-center md:text-left">
            <ul class="space-y-4">
                <li class="flex gap-2">
                    <a href="https://play.google.com/store/apps/details?id=com.moboeats.orderer" class="">
                        <img src="{{ asset('assets/img/play-store.png') }}" alt="" class="w-52 object-contain">
                    </a>
                    <a href="https://play.google.com/store/apps/details?id=com.moboeats.orderer" class="">
                        <img src="{{ asset('assets/img/app-store.png') }}" alt="" class="w-52 object-contain">
                    </a>
                </li>
                <li><a href="https://play.google.com/store/apps/details?id=com.moboeats.partners" target="_blank" class="font-bold hover:text-primary-two">Register your Convenient Store</a></li>
                <li><a href="https://restaurant.moboeats.com/signup" target="_blank" class="font-bold hover:text-primary-two">Register your Restaurant</a></li>
                <li><a href="https://play.google.com/store/apps/details?id=com.moboeats.driver" target="_blank" class="font-bold hover:text-primary-two">Become a Driver</a></li>
            </ul>
        </div>
        <div class="">
            <ul class="py-2 flex gap-4 justify-center">
                <li class="px-0.5">
                    <a href="https://www.instagram.com/officialmoboeats?igsh=MWtjdjhtbGZ1NTM3bQ%3D%3D&utm_source=qr" target="_blank" class="">
                        <img src="{{ asset('assets/img/instagram-color.png') }}" alt="" class="w-10 h-10">
                    </a>
                </li>
                <li class="">
                    <a href="https://www.linkedin.com/company/mobo-eats" target="_blank" class="">
                        <img src="{{ asset('assets/img/linkedin-color.png') }}" alt="" class="w-9 h-9">
                    </a>
                </li>
                <li class="px-1">
                    <a href="https://www.facebook.com/profile.php?id=61557389856966" target="_blank" class="">
                        <img src="{{ asset('assets/img/facebook-color.png') }}" alt="" class="w-9 h-9">
                    </a>
                </li>
                {{-- <li class="-mt-1">
                    <a href="#" class="">
                        <img src="{{ asset('assets/img/tiktok.png') }}" alt="" class="w-11 h-11">
                    </a>
                </li> --}}
                <li class="mt-1">
                    <a href="https://twitter.com/moboeats" target="_blank" class="">
                        <img src="{{ asset('assets/img/x-color.png') }}" alt="" class="w-7 h-7">
                    </a>
                </li>
            </ul>
        </div>
        <div class="text-center md:text-left">
            <ul class="space-y-4">
                {{-- <li><button class="font-bold hover:text-primary-two" data-modal-target="courier-terms-and-conditions" data-modal-toggle="courier-terms-and-conditions">Courier Terms and Conditions</button></li>
                <li><button class="font-bold hover:text-primary-two" data-modal-target="alcohol-delivery-guidelines" data-modal-toggle="alcohol-delivery-guidelines">Alcohol Delivery Guidelines</button></li>
                <li><button class="font-bold hover:text-primary-two" data-modal-target="courier-training" data-modal-toggle="courier-training">Courier Training</button></li>
                <li><button class="font-bold hover:text-primary-two" data-modal-target="merchant-payment-policy" data-modal-toggle="merchant-payment-policy">Merchant Payment Policy</button></li>
                <li><button class="font-bold hover:text-primary-two" data-modal-target="partnership-terms-and-conditions" data-modal-toggle="partnership-terms-and-conditions">Partnership Terms and Conditions</button></li> --}}
                <li><button class="font-bold hover:text-primary-two" data-modal-target="privacy" data-modal-toggle="privacy">Privacy</button></li>
                <li><button class="font-bold hover:text-primary-two" data-modal-target="refund-policy" data-modal-toggle="refund-policy">Refund Policy</button></li>
                <li><button class="font-bold hover:text-primary-two" data-modal-target="cookie-policy" data-modal-toggle="cookie-policy">Cookie Policy</button></li>
                <li><button class="font-bold hover:text-primary-two" data-modal-target="customer-terms-and-conditions" data-modal-toggle="customer-terms-and-conditions">Terms and Conditions</button></li>
                <li><button class="font-bold hover:text-primary-two" data-modal-target="modern-slavery-statement" data-modal-toggle="modern-slavery-statement">Modern Day Slavery Statement</button></li>
            </ul>
        </div>

        <!-- Main modal -->
        <div id="customer-terms-and-conditions" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto bg-[#113c22] overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
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

        {{-- <div id="courier-terms-and-conditions" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto bg-[#113c22] overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-4xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-primary-one rounded-lg shadow">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-white">
                            Courier Agreement
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="courier-terms-and-conditions">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <span class="font-bold">
                            This agreement ("Agreement") is entered into between Mobo Eats, hereinafter referred to as "the
                            Company," and the Rider, hereinafter referred to as "the Courier".
                        </span>
                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Engagement of Services:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The Courier agrees to provide food and grocery delivery
                                    services to customers on behalf of the Company using their own vehicle (bicycle,
                                    scooter, motorcycle, car, etc.) and equipment necessary for safe and efficient deliveries.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Independent Contractor Status:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The Courier acknowledges and agrees that they are
                                    an independent contractor and not an employee of the Company. The Rider has the
                                    freedom to determine their own working hours and is responsible for their own taxes,
                                    insurance, and other obligations.
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
                                    Eligibility:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The courier warrants that they are legally eligible to work in the country where
                                    they operate, possess a valid driver's license, vehicle registration, and insurance (if
                                    applicable), and are at least 18 years old.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Delivery Standards:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    Courier agrees to adhere to the Company's delivery standards,
                                    including but not limited to:
                                </p>
                                <ul class="list-desc">
                                    <li>Delivering orders within the specified timeframe.</li>
                                    <li>Handling food and groceries with care to maintain quality and hygiene.</li>
                                    <li>Following all traffic laws and safety regulations during deliveries.</li>
                                    <li>Maintaining a professional demeanour and providing excellent customer
                                        service.</li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Accepting Orders:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The Courier has the right to accept or decline delivery orders based
                                    on their availability, location, and other factors. Once an order is accepted, the Courier
                                    must fulfil it promptly and professionally.
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
                                    Compensation:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The Courier's compensation will be based on the number of deliveries
                                    completed, distance travelled, and other relevant factors as outlined in the Company's
                                    payment policy. Details of payment rates and schedules will be provided separately.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Confidentiality:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The Courier agrees to maintain the confidentiality of any sensitive
                                    information obtained during their work, including customer data, order details, and
                                    Company policies.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Safety and Security:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The Courier acknowledges that their safety is paramount. They
                                    must prioritize personal safety, follow all safety guidelines provided by the Company,
                                    and report any accidents or incidents promptly.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Termination:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    Either party may terminate this Agreement with notice. The Company
                                    reserves the right to terminate the Agreement immediately in case of serious breaches
                                    of terms or improper conduct by the Rider.
                                </p>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Updates to Agreement:
                                </span>
                                <p class="font-semibold text-base leading-relaxed text-slate-50">
                                    The Company reserves the right to update or modify this
                                    Agreement as needed. Any changes will be communicated to the Courier in a timely
                                    manner.
                                </p>
                            </li>
                        </ul>
                        <span class="font-bold mt-12">
                            By continuing to work a courier for Mobo Eats, you acknowledge that you have read,
                            understood, and agreed to abide by the terms and conditions outlined in this Agreement. Thank
                            you for being a valuable part of our team! If you have any questions or concerns, please contact us at
                            <a href="mailto:info@moboeats.co.uk." class="text-primary-one underline">info@moboeats.co.uk.</a>
                        </span>
                    </div>
                </div>
            </div>
        </div> --}}

        {{-- <div id="alcohol-delivery-guidelines" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto bg-[#113c22] overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-4xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-primary-one rounded-lg shadow">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-white">
                            Alcohol Delivery Guidelines
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="alcohol-delivery-guidelines">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <h2 class="underline text-primary-one font-bold">Alcohol Orders</h2>
                        <span class="font-bold">
                            <span>
                                Alcohol delivery is optional, and opting out of this type of delivery will not impact your
                                ability to receive other orders from restaurants, grocery stores, retail partners, and more. If
                                you wish to opt out of this type of delivery, please submit your request to <a href="mailto:info@moboeats.co.uk" class="text-primary-two underline">info@moboeats.co.uk.</a>
                            </span>
                        </span>
                        <span class="font-bold">
                            In addition, if you ever receive an alcohol order you do not wish to perform, you have the option
                            to decline the order before accepting it or cancel the order before picking it up from the
                            merchant.
                        </span>
                        <span class="font-bold">
                            <h2 class="underline text-primary-one">Alcohol Delivery</h2>
                            While on a delivery, you may see orders containing alcoholic beverages, and the following
                            information is intended to help you lawfully deliver alcohol with Mobo Eats. You could be held
                            liable if you deliver alcohol to a minor or someone who is visibly intoxicated or if you leave the
                            alcohol unattended, so it’s important that you know what to do to protect yourself and the
                            customer.
                        </span>
                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Important Reminders:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>You must check the recipient’s identity by following instructions in the Dasher app
                                        before you hand them an order containing alcohol. You will be prompted to check and
                                        verify the ID and enter the recipient date of birth on your rider app.
                                    </li>
                                    <li>Visually compare the recipient’s appearance to the ID they provide. You cannot leave an
                                        alcohol order unattended or hand it to someone else who cannot produce a valid ID.
                                    </li>
                                    <li>Couriers cannot consume alcohol prior to or while delivery—or be under the influence
                                        of anything.
                                    </li>
                                    <li>Couriers must be of legal drinking age (18+) and have a valid government-issued ID to
                                        deliver orders containing alcohol.</li>
                                </ul>
                            </li>
                        </ul>
                        <span class="font-bold my-8">
                            You may be held criminally liable if you provide alcohol to a person who is underage or
                            intoxicated or leave alcohol unattended.
                        </span>
                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Liability for an unlawful alcohol delivery may include:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Fines or jail time.
                                    </li>
                                    <li>
                                        Civil lawsuits that individuals may bring against delivery persons to recover damages for
                                        personal injury or property resulting from an unlawful alcohol delivery, which typically
                                        results in a monetary fine (but a criminal action can result in jail time).
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        <span class="font-bold my-4">
                            Mobo Eats may also take such other remedial action up to and including deactivation from
                            Mobo Eats Courier platform. Remember, we’re here to support you! If you have any questions
                            before, during, or after a delivery, get in touch with us through the Dasher app.
                        </span>

                        <span class="font-bold my-4">
                            <h2 class="underline text-primary-one">The following is the Dasher’s guide in Delivering Alcohol.</h2>
                            <span class="font-semibold my-2">
                                Courier must be of legal drinking age and have a valid government-issued ID to deliver orders
                                containing alcohol. Show merchant you have started the order when collected.
                            </span>
                            <span class="font-semibold my-2">
                                As a courier, you must read and understand the alcohol delivery guidelines and must accept
                                Mobo Eats terms and conditions before your first delivery.
                            </span>
                        </span>

                        <span class="font-bold mt-12">
                            By continuing to work a courier for Mobo Eats, you acknowledge that you have read,
                            understood, and agreed to abide by the terms and conditions outlined in this Agreement. Thank
                            you for being a valuable part of our team! If you have any questions or concerns, please contact us at
                            <a href="mailto:info@moboeats.co.uk." class="text-primary-one underline">info@moboeats.co.uk.</a>
                        </span>
                    </div>
                </div>
            </div>
        </div> --}}

        {{-- <div id="courier-training" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto bg-[#113c22] overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-4xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-primary-one rounded-lg shadow">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-white">
                            Welcome to Moboeats
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="courier-training">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <span class="font-bold">
                            We are excited to have you on board. To ensure that you provide exceptional service to our
                            customers and represent our company well, we have developed a comprehensive training
                            program for delivering food. Please review and follow the guidelines outlined below:
                        </span>
                        <ul class="list-decimal space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Orientation Session:
                                </span>
                                <ul class="list-decimal mx-4 font-semibold">
                                    <li>Familiarise yourself with company policies, values, and expectations.</li>
                                    <li>Learn about the delivery app/system we use, including how to accept orders,
                                        navigate routes, and communicate with customers and support teams.</li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Vehicle and Equipment Preparation:
                                </span>
                                <ul class="list-decimal mx-4 font-semibold">
                                    <li>Ensure that your delivery vehicle (bicycle, scooter, motorcycle, car, etc.) is in
                                        good working condition.
                                    </li>
                                    <li>Equip yourself with a thermal delivery bag or container to maintain food
                                        freshness during transportation.
                                    </li>
                                    <li>Carry necessary safety gear such as a helmet, reflective vest (if applicable), and
                                        any other equipment required by local regulations.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Food Safety and Hygiene:
                                </span>
                                <ul class="list-decimal mx-4 font-semibold">
                                    <li>Understand and adhere to food safety standards, including proper handling and
                                        storage of food items.
                                    </li>
                                    <li>Use the thermal bag/container to keep hot foods hot and cold foods cold to
                                        maintain quality and safety.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Customer Service Skills:
                                </span>
                                <ul class="list-decimal mx-4 font-semibold">
                                    <li>Demonstrate excellent communication skills and professionalism when
                                        interacting with customers, merchant staff, and support teams.
                                    </li>
                                    <li>Be courteous, friendly, and responsive to customer inquiries and special
                                        requests.
                                    </li>
                                    <li>Handle customer complaints or issues calmly and seek assistance from the
                                        support team when needed.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Navigation and Route Optimization:
                                </span>
                                <ul class="list-decimal mx-4 font-semibold">
                                    <li>Familiarise yourself with delivery routes in your assigned area to ensure timely
                                        and efficient deliveries.
                                    </li>
                                    <li>Use GPS navigation tools or the delivery app's routing system to navigate
                                        accurately and avoid delays.
                                    </li>
                                    <li>Optimize routes to maximize the number of deliveries while maintaining delivery
                                        time commitments.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Delivery Protocols:
                                </span>
                                <ul class="list-decimal mx-4 font-semibold">
                                    <li>Follow the company's delivery protocols, including pickup procedures from
                                        restaurants or stores and contactless delivery options if available.
                                    </li>
                                    <li>Verify order accuracy and completeness before leaving the pickup location.
                                    </li>
                                    <li>Obtain customer ID, signatures or confirmations upon delivery, if required.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Safety and Compliance:
                                </span>
                                <ul class="list-decimal mx-4 font-semibold">
                                    <li>Obey all traffic laws and regulations while on the road.
                                    </li>
                                    <li>Prioritise your safety and the safety of others by wearing appropriate safety gear
                                        and maintaining safe driving practices.
                                    </li>
                                    <li>Report any accidents, incidents, or safety concerns to the support team
                                        immediately.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Time Management and Efficiency:
                                </span>
                                <ul class="list-decimal mx-4 font-semibold">
                                    <li>Manage your time effectively to fulfil delivery orders within the specified
                                        timeframe.
                                    </li>
                                    <li>Plan your routes to minimize idle time and optimize delivery efficiency.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Continuous Improvement:
                                </span>
                                <ul class="list-decimal mx-4 font-semibold">
                                    <li>Seek feedback from customers and colleagues to identify areas for
                                        improvement.
                                    </li>
                                    <li>Participate in ongoing training sessions and updates to stay informed about new
                                        policies, technologies, and best practices.
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="flex flex-col space-y-4">
                        <span class="font-bold space-y-3 mx-4">
                            Remember, you are an important ambassador for Mobo Eats, and your dedication to providing
                            quality service reflects positively on our brand. Thank you for your commitment to excellence in
                            food delivery!
                        </span>
                        <span class="font-bold space-y-3 mx-4">
                            If you have any questions or need further assistance during your training, please don't hesitate
                            to reach out to your Courier support team. Information will be provided in app.
                        </span>
                        <span class="font-bold mx-4 pb-4">Best Regards</span>
                    </div>
                </div>
            </div>
        </div> --}}

        {{-- <div id="merchant-payment-policy" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto bg-[#113c22] overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-4xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-primary-one rounded-lg shadow">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-white">
                            Payment Policy
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="merchant-payment-policy">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Payment Settlement:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Payments for orders fulfilled through our food dine in and grocery delivery
                                        platform will be settled based on agreed-upon terms between Mobo Eats and
                                        the merchant.
                                    </li>
                                    <li>Settlements may occur weekly or monthly, depending on the payment schedule
                                        agreed upon during the merchant onboarding process.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Commission and Fees:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Mobo Eats may charge commission or service fee for facilitating orders and
                                        delivery services through our platform.
                                    </li>
                                    <li>The commission rate and any applicable fees will be clearly communicated to
                                        the merchant before entering into a partnership agreement.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Payment Calculation:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>The payment amount to the merchant will be calculated based on the total value
                                        of orders fulfilled minus any applicable commissions, fees, or deductions as per
                                        the agreement.
                                    </li>
                                    <li>Taxes and other government-mandated deductions may apply and will be
                                        deducted as required by law.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Invoicing and Statements:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Merchants will receive detailed invoices or statements outlining the
                                        transactions, commission charges, fees, and net payment amounts.
                                    </li>
                                    <li>Invoices or statements will typically be provided electronically through our
                                        merchant portal or via email.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Payment Methods:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Payments to merchants will be made through the agreed-upon payment
                                        method(s), which may include:
                                        <ul class="list-decimal font-semibold mx-6 space-y-2">
                                            <li>Bank transfers: Direct deposits to the merchant's designated bank
                                                account.
                                            </li>
                                            <li>Online payment platforms: Secure electronic transfers via payment
                                                gateways.
                                            </li>
                                            <li>Checks: Physical checks issued to the merchant's mailing address (if
                                                applicable).
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Payment Disputes and Resolutions:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>In case of payment discrepancies or disputes, merchants must notify Mobo Eats
                                        promptly to initiate a resolution process.
                                    </li>
                                    <li>Both parties agree to resolve payment disputes amicably through
                                        communication and negotiation.
                                    </li>
                                    <li>If a resolution cannot be reached, arbitration or legal measures may be pursued
                                        as per the terms of the partnership agreement.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Late Payments and Penalties:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Merchants are expected to adhere to the payment schedule outlined in the
                                        agreement.
                                    </li>
                                    <li>Late payments may incur penalties, such as interest charges or suspension of
                                        services, as per the terms of the agreement and applicable laws.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Currency and Taxes:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>All payments will be made in the agreed currency (e.g., GBP, USD, EUR, etc.)
                                        unless otherwise specified.
                                    </li>
                                    <li>Merchants are responsible for any taxes, duties, or fees imposed by local
                                        authorities related to the services provided through our platform.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Updates to Payment Policy:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>This payment policy is subject to updates or modifications with mutual
                                        agreement between Mobo Eats and the merchant.
                                    </li>
                                    <li>Any changes to the payment policy will be communicated in writing and
                                        acknowledged by both parties.
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <span class="mx-4 space-y-4 font-bold">
                        By partnering with Mobo Eats, merchants acknowledge that they have read, understood, and
                        agreed to abide by the terms and conditions outlined in this payment policy.
                    </span>
                </div>
            </div>
        </div> --}}

        {{-- <div id="partnership-terms-and-conditions" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto bg-[#113c22] overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-4xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-primary-one rounded-lg shadow">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-white">
                            Terms and Conditions
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="partnership-terms-and-conditions">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <span class="font-bold">
                            Welcome to Mobo Eats! We are delighted to partner with you as a merchant to our customers
                            through our platform. Please carefully review and understand the following terms and
                            conditions governing our partnership:
                        </span>
                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Partnership Agreement:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>By registering as a merchant with Mobo Eats, you agree to enter into a
                                        partnership agreement to sell your products through our platform.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Merchant Responsibilities:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Provide accurate and up-to-date information about your business, including
                                        menu items, prices, availability, and business hours.
                                    </li>
                                    <li>Maintain high-quality standards for food and grocery items, ensuring freshness,
                                        cleanliness, and adherence to food safety regulations.
                                    </li>
                                    <li>Fulfil orders promptly within the specified time frame agreed upon with
                                        customers and our platform.
                                    </li>
                                    <li>Notify us immediately of any changes to your menu, prices, availability, or
                                        operational status that may affect orders.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Order Management:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Acknowledge and accept orders received through our platform within a
                                        reasonable time frame.
                                    </li>
                                    <li>Ensure that orders are prepared accurately and packaged securely to maintain
                                        quality during delivery.
                                    </li>
                                    <li>Confirm the driver as they start their delivery.
                                    </li>
                                    <li>Communicate any delays, cancellations, or issues with orders to our platform
                                        and customers promptly.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Pricing and Payment:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Set and update prices for your products in accordance with our platform's
                                        guidelines and pricing policies.
                                    </li>
                                    <li>Receive payments for orders through our platform based on agreed-upon terms
                                        and payment schedules.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Quality Assurance:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Comply with all applicable laws, regulations, and industry standards related to
                                        food safety, hygiene, and quality control.
                                    </li>
                                    <li>Address customer complaints or issues regarding product quality, order
                                        accuracy, or service promptly and professionally.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Delivery and Pickup Options:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Offer delivery, pickup and dine in options based on your operational capabilities
                                        and agreements with our platform.
                                    </li>
                                    <li>Ensure that delivery personnel (if applicable) representing your business
                                        maintain professionalism, adhere to traffic laws, and follow safety protocols
                                        during deliveries.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Intellectual Property:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Retain ownership of your intellectual property, including trademarks, logos, and
                                        content provided to our platform.
                                    </li>
                                    <li>Grant Mobo Eats Ltd a limited, non-exclusive license to use your intellectual
                                        property solely for the purpose of promoting and facilitating transactions on our
                                        platform.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Data and Privacy:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Protect customer data and privacy in accordance with applicable data
                                        protection laws and our privacy policy.
                                    </li>
                                    <li>Use customer data obtained through our platform only for order fulfilment and
                                        customer service purposes.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Termination of Partnership:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Either party may terminate this partnership agreement with notice of 1 month.
                                    </li>
                                    <li>We reserve the right to suspend or terminate your merchant account on our
                                        platform in case of non-compliance with these terms and conditions, breach of
                                        contract, or other violations.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Updates to Terms:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Mobo Eats reserves the right to update or modify these terms and conditions as
                                        needed. We will communicate any changes to you in a timely manner.
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <span class="mx-4 space-y-4 font-bold">
                        By continuing to partner with Mobo Eats Ltd, you acknowledge that you have read, understood,
                        and agreed to abide by these terms and conditions. We look forward to a successful and
                        mutually beneficial partnership.
                    </span>
                </div>
            </div>
        </div> --}}

        <div id="refund-policy" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto bg-[#113c22] overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-4xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-primary-one rounded-lg shadow">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-white">
                            Refund Policy
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="refund-policy">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <span class="font-bold">
                            At Mobo Eats, we strive to provide excellent service to our customers. However, we understand
                            that situations may arise where a refund is necessary. Please read our refund policy carefully to
                            understand how refunds are processed for orders placed through our food and grocery delivery
                            app:
                        </span>
                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Order Cancellation and Refund Eligibility:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Customers may request order cancellations and refunds under certain
                                        circumstances, such as:
                                        <ul class="list-decimal font-semibold mx-6 space-y-2">
                                            <li>Incorrect or missing items in the order.
                                            </li>
                                            <li>Quality issues with delivered food or grocery items.
                                            </li>
                                            <li>Food arriving cold.
                                            </li>
                                            <li>Payment processing errors or unauthorized charges.
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Refund Request Process:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Customers must contact our customer support team to request a refund within
                                        a reasonable time frame after the delivery is completed or the issue is identified.
                                    </li>
                                    <li>Refund requests may require providing relevant details such as order number,
                                        description of the issue, and any supporting evidence (e.g., photos of damaged
                                        items).
                                    </li>
                                    <li>Our customer support team will review each refund request on a case-by-case
                                        basis and determine eligibility based on our refund policy.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Refund Methods:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Approved refunds will be processed using the original payment method used for
                                        the order, unless otherwise specified by the customer or if technical limitations
                                        apply.
                                    </li>
                                    <li>Refunds may be issued as:
                                        <ul class="list-decimal font-semibold mx-6 space-y-2">
                                            <li>Full refunds: If the entire order is eligible for a refund.
                                            </li>
                                            <li>Partial refunds: If only part of the order is affected or if discounts or
                                                promotions were applied.
                                            </li>
                                            <li>Credit refunds: In some cases, customers may receive store credits or
                                                loyalty points as refunds, especially for promotional or goodwill
                                                purposes.
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Timelines for Refunds:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Refunds will be processed as soon as possible after approval, typically within 3-
                                        7 business days.
                                    </li>
                                    <li>The actual timeline for receiving the refunded amount may vary depending on
                                        the customer's bank or payment provider.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Exceptions to Refund Policy:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Certain items or circumstances may not be eligible for refunds, such as:
                                        <ul class="list-decimal font-semibold mx-6 space-y-2">
                                            <li>Orders that have already been consumed or partially consumed.
                                            </li>
                                            <li>Situations where the customer provided incorrect delivery information or
                                                failed to receive the delivery without notifying us.
                                            </li>
                                            <li>Issues arising from customer preferences or changes of mind (e.g.,
                                                dissatisfaction with taste or portion size).
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Dispute Resolution:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>In case of disputes or disagreements regarding refunds, customers and Mob
                                        Eats agree to resolve the issue amicably through communication and mediation.
                                    </li>
                                    <li>If a satisfactory resolution cannot be reached, customers may escalate the
                                        matter to relevant consumer protection authorities or regulatory bodies as per
                                        applicable laws.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Intellectual Property:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Retain ownership of your intellectual property, including trademarks, logos, and
                                        content provided to our platform.
                                    </li>
                                    <li>Grant Mobo Eats Ltd a limited, non-exclusive license to use your intellectual
                                        property solely for the purpose of promoting and facilitating transactions on our
                                        platform.
                                    </li>
                                </ul>
                            </li>
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Policy Updates:
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>Mobo Eats reserves the right to update or modify this refund policy as needed.
                                        Any changes will be communicated to customers through our app, website, or
                                        customer support channels.
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="flex flex-col space-y-3">
                        <span class="mx-4 space-y-4 font-bold">
                            By using our food, dine in and grocery delivery app, customers acknowledge and agree to abide
                            by this refund policy. If you have any questions or need assistance regarding refunds, please
                            contact our customer support team at <a href="mailto:info@moboeats.co.uk" class="text-primary-two">info@moboeats.co.uk</a>.
                        </span>
                        <span class="mx-4 font-bold pb-3">
                            Thank you for choosing Mobo Eats for your delivery needs.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div id="cookie-policy" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto bg-[#113c22] overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-4xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-primary-one rounded-lg shadow">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-white">
                            Cookie Policy
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="refund-policy">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <span class="font-bold">
                            This Cookie Policy explains how we use cookies and similar technologies on our website and
                            mobile application (collectively referred to as "platform"). By using our platform, you consent to
                            the use of cookies and similar technologies as described in this policy.
                        </span>
                        <br>
                        <div class="mt-10">
                            <span class="font-bold text-lg">
                                What are cookies?
                            </span>
                            <br>
                            <span class="font-bold">
                                Cookies are small text files that are stored on your device (computer, smartphone, or tablet)
                                when you visit a website or use a mobile application. They are widely used to enhance user
                                experience, analyse website usage, and provide personalized content and advertisements.
                            </span>
                        </div>
                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Types of Cookies We Use
                                </span>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>
                                        <span class="font-semibold">Essential cookies:</span>
                                        <span class="font-thin">
                                            These cookies are necessary for the operation of our platform and
                                            enable you to navigate and use its features. Without these cookies, certain
                                            functionalities may not be available.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Performance Cookies:</span>
                                        <span class="font-thin">
                                            These cookies collect information about how visitors use our
                                            platform, such as which pages are visited most frequently and if users encounter error
                                            messages. This helps us improve the performance and usability of our platform.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Functionality Cookies:</span>
                                        <span class="font-thin">
                                            These cookies allow our platform to remember choices you
                                            make (such as your language preferences or location) and provide enhanced, more
                                            personalized features.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Advertising Cookies:</span>
                                        <span>
                                            These cookies are necessary for the operation of our platform and
                                            enable you to navigate and use its features. Without these cookies, certain
                                            functionalities may not be available.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Essential cookies</span>
                                        <span class="font-thin">
                                            These cookies are used to deliver advertisements that are more
                                            relevant to you and your interests. They may also be used to limit the number of times
                                            you see an advertisement and measure the effectiveness of advertising campaigns.
                                        </span>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                        <div class="mt-10">
                            <span class="font-bold">Third-Party Cookies</span>
                            <div>
                                <span class="font-thin">
                                    We may also use third-party cookies provided by trusted partners for analytics, advertising, and
                                    other purposes. These third parties may collect information about your online activities over
                                    time and across different websites and applications.
                                </span>
                            </div>
                        </div>
                        <div class="mt-10">
                            <span class="font-bold">Your Cookie Choices</span>
                            <div>
                                <span class="font-thin">
                                    You have the option to accept or reject cookies through your browser settings. Most web
                                    browsers automatically accept cookies, but you can usually modify your browser settings to
                                    decline cookies if you prefer. Please note that disabling cookies may impact the functionality
                                    and performance of our platform.
                                </span>
                            </div>
                        </div>
                        <div class="mt-10">
                            <span class="font-bold">Cookie Management</span>
                            <div>
                                <span class="font-thin">
                                    You can manage your cookie preferences and settings directly through your browser or device
                                    settings. Additionally, you can opt-out of certain third-party cookies by visiting the opt-out pages
                                    provided by the relevant third parties.
                                </span>
                            </div>
                        </div>
                        <div class="mt-10">
                            <span class="font-bold">Changes to this Cookie Policy</span>
                            <div>
                                <span class="font-thin">
                                    We may update this Cookie Policy from time to time to reflect changes in our practices or legal
                                    requirements. We will notify you of any material changes by posting the updated policy on our
                                    platform. Your continued use of our platform after the posting of changes constitutes your
                                    acceptance of such changes.
                                </span>
                            </div>
                        </div>
                        <div class="mt-10">
                            <span class="font-bold">Contact Us</span>
                            <div>
                                <span class="font-thin">
                                    If you have any questions or concerns about our use of cookies or this Cookie Policy, please contact us at <a href="mailto:info@moboeats.co.uk" class="text-primary-two underline font-semibold">info@moboeats.co.uk</a>.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="privacy" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto bg-[#113c22] overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-4xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-primary-one rounded-lg shadow">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-white">
                            Privacy
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="refund-policy">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <span class="font-bold">
                            We are committed to protecting the privacy and security of your personal information. This
                            Privacy Policy outlines the types of information we collect, how we use it, and the choices you
                            have regarding your information.
                        </span>
                        <br>
                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Information We Collect
                                </span>
                                <div>
                                    <span class="font-thin">
                                        We may collect personal information from you when you interact with our website, mobile application, or other services we provide. This information may include:
                                    </span>
                                </div>
                                <ul class="list-decimal font-semibold mx-4 space-y-2 mt-5">
                                    <li>
                                        <span class="font-semibold">Personal Information:</span>
                                        <span class="font-thin">
                                            such as your name, email address, phone number, and delivery address.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Payment Information:</span>
                                        <span class="font-thin">
                                            such as credit card details or other payment information necessary to process your orders.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Location Information:</span>
                                        <span class="font-thin">
                                            if you enable location services on your device, we may collect your precise location to provide you with relevant services, such as delivery tracking.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Order History:</span>
                                        <span class="font-thin">
                                            details of your orders, including the items purchased, delivery preferences, and any special instructions.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Device Information:</span>
                                        <span class="font-thin">
                                            information about the device you use to access our services, such as your IP address, browser type, and operating system.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Cookies and Tracking Technologies:</span>
                                        <span class="font-thin">
                                            we may use cookies and similar technologies to enhance your experience, analyse trends, and track your interactions with our services.
                                        </span>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    How We Use Your Information
                                </span>
                                <div>
                                    <span class="font-thin">
                                        We may use the information we collect for the following purposes:
                                    </span>
                                </div>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>
                                        <span class="font-semibold">Providing Services:</span>
                                        <span class="font-thin">
                                            to process your orders, deliver food, booking dining and communicate with you about your orders.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Improving Services:</span>
                                        <span class="font-thin">
                                            to analyse trends, monitor usage patterns, and improve the functionality and performance of our services.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Personalization:</span>
                                        <span class="font-thin">
                                            to personalize your experience and provide you with relevant content, promotions, and recommendations.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Customer Support:</span>
                                        <span class="font-thin">
                                            to respond to your inquiries, address your concerns, and provide customer support.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Marketing:</span>
                                        <span class="font-thin">
                                            to send you promotional materials, offers, and updates about our services, with your consent where required by law.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Legal Compliance:</span>
                                        <span class="font-thin">
                                            to comply with legal obligations, enforce our terms and policies, and protect the rights, property, or safety of our users or others.
                                        </span>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Information Sharing
                                </span>
                                <div>
                                    <span class="font-thin">
                                        We may share your personal information with third parties under the following circumstances:
                                    </span>
                                </div>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>
                                        <span class="font-semibold">Service Providers:</span>
                                        <span class="font-thin">
                                            we may engage third-party service providers to perform functions on
                                            our behalf, such as processing payments, delivering orders, or analysing data. These
                                            service providers will have access to your personal information only to the extent
                                            necessary to perform their functions.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Business Transfers:</span>
                                        <span class="font-thin">
                                            in the event of a merger, acquisition, or sale of all or a portion of our
                                            assets, your personal information may be transferred as part of the transaction. We will
                                            notify you via email or prominent notice on our website of any change in ownership or
                                            uses of your personal information.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Legal Requirements:</span>
                                        <span class="font-thin">
                                            we may disclose your information if required to do so by law or in response to a valid legal request, such as a court order or subpoena.
                                        </span>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Your Choices
                                </span>
                                <div>
                                    <span class="font-thin">
                                        You have certain rights and choices regarding your personal information:
                                    </span>
                                </div>
                                <ul class="list-decimal font-semibold mx-4 space-y-2">
                                    <li>
                                        <span class="font-semibold">Access and Update:</span>
                                        <span class="font-thin">
                                            you may access, update, or correct your personal information by logging into your account or contacting us directly.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Opt-Out:</span>
                                        <span class="font-thin">
                                            you may opt-out of receiving promotional emails by following the instructions provided in the email or contacting us.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Location Services:</span>
                                        <span class="font-thin">
                                            you can disable location services on your device or adjust your device settings to prevent us from accessing your location information.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Cookies:</span>
                                        <span class="font-thin">
                                            you can set your browser to refuse all or some browser cookies or to alert you
                                            when websites set or access cookies. However, if you disable or refuse cookies, please
                                            note that some parts of our services may become inaccessible or not function properly.
                                        </span>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        <div class="mt-10">
                            <span class="font-bold text-lg">
                                Data Security
                            </span>
                            <br>
                            <span class="font-thin">
                                We have implemented measures to protect the security of your personal information and
                                prevent unauthorized access, use, or disclosure. However, please be aware that no method of
                                transmission over the internet or electronic storage is 100% secure, and we cannot guarantee
                                absolute security.
                            </span>
                        </div>

                        <div class="mt-10">
                            <span class="font-bold text-lg">
                                Children's Privacy
                            </span>
                            <br>
                            <span class="font-thin">
                                Our services are not intended for children under the age of 13, and we do not knowingly collect
                                personal information from children under the age of 13. If you believe we have collected
                                personal information from a child under 13, please contact us immediately.
                            </span>
                        </div>

                        <div class="mt-10">
                            <span class="font-bold text-lg">
                                Changes to this Privacy Policy
                            </span>
                            <br>
                            <span class="font-thin">
                                We may update this Privacy Policy from time to time to reflect changes in our practices or legal
                                requirements. We will notify you of any material changes by posting the updated Privacy Policy
                                on our website or mobile application and updating the "Last Updated" date at the top of this
                                page.
                            </span>
                        </div>

                        <div class="mt-10">
                            <span class="font-bold">Contact Us</span>
                            <div>
                                <span class="font-thin">
                                    If you have any questions or concerns about our use of cookies or this Cookie Policy, please contact us at <a href="mailto:info@moboeats.co.uk" class="text-primary-two underline font-semibold">info@moboeats.co.uk</a>.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="modern-slavery-statement" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto bg-[#113c22] overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-4xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-primary-one rounded-lg shadow">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-white">
                            Modern Slavery Statement
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="refund-policy">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <span class="font-bold">
                            Mobo Eats is committed to combatting modern slavery and human trafficking in all aspects of
                            our business and supply chain. We recognize the importance of upholding human rights and
                            ethical labour practices, and we are dedicated to ensuring that our operations are conducted in
                            a responsible and sustainable manner.
                        </span>
                        <br>
                        <ul class="list-disc space-y-3">
                            <li class="flex flex-col">
                                <span class="font-bold text-lg">
                                    Our Approach
                                </span>
                                <div>
                                    <span class="font-thin">
                                        We believe that transparency, accountability, and collaboration are essential in addressing modern slavery risks effectively. Our approach to combating modern slavery includes:
                                    </span>
                                </div>
                                <ul class="list-decimal font-semibold mx-4 space-y-2 mt-5">
                                    <li>
                                        <span class="font-semibold">Policies and Procedures:</span>
                                        <span class="font-thin">
                                            We have established policies and procedures that prohibit
                                            modern slavery and human trafficking in our operations and supply chain. These
                                            policies are communicated to all employees and suppliers, emphasizing our zero-
                                            tolerance stance towards any form of exploitation.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Supplier Due Diligence:</span>
                                        <span class="font-thin">
                                            We conduct due diligence assessments of our suppliers to
                                            evaluate their adherence to ethical labour practices. This includes assessing supplier
                                            policies, conducting site visits, and engaging in dialogue to understand their approach
                                            to labour rights and social responsibility.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Training and Awareness:</span>
                                        <span class="font-thin">
                                            We provide training and awareness programs to our
                                            employees to educate them about the risks of modern slavery and empower them to
                                            identify and report any concerns. We also engage with our suppliers to raise awareness
                                            of modern slavery issues and promote responsible sourcing practices.
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Monitoring and Compliance:</span>
                                        <span class="font-thin">
                                            We regularly monitor and evaluate our supply chain to
                                            identify and address any potential instances of modern slavery. This includes
                                            conducting audits, implementing corrective actions, and collaborating with
                                            stakeholders to drive continuous improvement.
                                        </span>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        <div class="mt-10">
                            <span class="font-bold text-lg">
                                Reporting Mechanism
                            </span>
                            <br>
                            <span class="font-thin">
                                If you have any concerns or suspicions regarding modern slavery or human trafficking within our
                                operations or supply chain, we encourage you to report them promptly through our confidential
                                reporting mechanism. Reports can be submitted anonymously, and all information will be
                                treated with the utmost confidentiality and sensitivity.
                            </span>
                        </div>

                        <div class="mt-10">
                            <span class="font-bold text-lg">
                                Conclusion
                            </span>
                            <br>
                            <span class="font-thin">
                                At Mobo Eats, we are committed to upholding the highest standards of integrity, ethics, and
                                respect for human rights. We recognize that eradicating modern slavery requires collective
                                effort and ongoing vigilance, and we remain steadfast in our commitment to combatting this
                                abhorrent crime.
                            </span>
                        </div>

                        <div class="mt-10">
                            <span class="font-bold">Kennedy King</span>
                            <div>
                                <span class="font-thin">
                                    CEO & Founder.
                                </span>
                            </div>
                            <div>
                                <span class="font-thin">{{ now()->format('Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

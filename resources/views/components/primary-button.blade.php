<button {{ $attributes->merge(['type' => 'submit', 'class' => 'px-4 bg-primary-one dark:bg-primary-one border border-transparent rounded-md font-semibold text-white tracking-tight dark:text-white hover:bg-orange-700 dark:hover:bg-orange-500 focus:bg-orange-700 dark:focus:bg-orange-400 active:bg-orange-400 dark:active:bg-orange-400 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:ring-offset-1 dark:focus:ring-offset-orange-500 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>

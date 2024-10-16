<header class="navbar border-b border-gray-200">
  <div class="flex-1">
    <a href="/" class="btn btn-ghost text-lg">ally<span class="border border-gray-300 inline-block text-sm px-1.5 py-0.5 relative left-[-2px] top-[1px]">BOX</span></a>
  </div>
  <div class="flex-none">
    <ul class="menu menu-horizontal px-1">
      <li><a href="/user/assistants">Assistants</a></li>
      <li>
        <details>
          <summary>Account</summary>
          <ul class="bg-base-100 rounded-t-none p-2 account-menu">
            <li><a href="/user/plans">Plans</a></li>
            <li><a href="/user/profile">Profile</a></li>
            <li>
              <div class="divider px-2 m-0"></div>
            </li>
            <li><a href="/wp-login.php?action=logout&_wpnonce=<?php echo wp_create_nonce('logout-nonce'); ?>">Logout</a></li>
          </ul>
        </details>
      </li>
    </ul>
  </div>
</header>
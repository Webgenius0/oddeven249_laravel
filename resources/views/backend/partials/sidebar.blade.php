   <!-- Page Sidebar Start-->
   <div class="sidebar-wrapper" data-layout="stroke-svg">
       <div class="logo-wrapper"><a href="index.html"><img class="img-fluid" src="{{ asset($setting->logo) }}"
                   alt=""></a>
           <div class="back-btn"><i class="fa fa-angle-left"> </i></div>
           <div class="toggle-sidebar"><i class="status_toggle middle sidebar-toggle" data-feather="grid">
               </i></div>
       </div>
       <div class="logo-icon-wrapper"><a href="index.html"><img class="img-fluid"
                   src="../assets/images/logo/logo-icon.png" alt=""></a></div>
       <nav class="sidebar-main">
           <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
           <div id="sidebar-menu">
               <ul class="sidebar-links" id="simple-bar">
                   <li class="back-btn"><a href="index.html"><img class="img-fluid"
                               src="../assets/images/logo/logo-icon.png" alt=""></a>
                       <div class="mobile-back text-end"> <span>Back </span><i class="fa fa-angle-right ps-2"
                               aria-hidden="true"></i></div>
                   </li>
                   <li class="pin-title sidebar-main-title">
                       <div>
                           <h6>Pinned</h6>
                       </div>
                   </li>
                   <li class="sidebar-main-title">
                       <div>
                           <h6 class="lan-1">General</h6>
                       </div>
                   </li>
                   <li class="sidebar-list"> <i class="fa fa-thumb-tack"></i><a
                           class="sidebar-link sidebar-title link-nav {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                           href="{{ route('admin.dashboard') }}">
                           <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                               fill="none" stroke="#ffffff" stroke-width="1" stroke-linecap="round"
                               stroke-linejoin="round">
                               <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                               <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                               <path d="M10 12h4v4h-4z" />
                           </svg>
                           <span>Dashboard</span></a></li>

                   <li class="sidebar-list">
                       <i class="fa fa-thumb-tack"></i>
                       <a class="sidebar-link sidebar-title
                      {{ request()->routeIs('admin.system.*') || request()->routeIs('admin.profile.*') || request()->routeIs('admin.social.*') ? 'active' : '' }}"
                           href="#">
                           <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                               fill="none" stroke="#ffffff" stroke-width="1" stroke-linecap="round"
                               stroke-linejoin="round">
                               <path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                               <path
                                   d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                               <path
                                   d="M14 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                               <path d="M14 7l6 0" />
                               <path d="M17 4l0 6" />
                           </svg>
                           <span>Settings</span>
                       </a>

                       <ul
                           class="sidebar-submenu {{ request()->routeIs('admin.system.*') || request()->routeIs('admin.profile.*') || request()->routeIs('admin.social.*') || request()->routeIs('admin.dynamic_page.*') ? 'd-block' : '' }}">
                           <li>
                               <a class="{{ request()->routeIs('admin.system.*') ? 'active' : '' }}"
                                   href="{{ route('admin.system.index') }}">
                                   System Settings
                               </a>
                           </li>
                           <li>
                               <a class="{{ request()->routeIs('admin.profile.*') ? 'active' : '' }}"
                                   href="{{ route('admin.profile.setting') }}">
                                   Profile Setting
                               </a>
                           </li>
                           <li>
                               <a class="{{ request()->routeIs('admin.social.*') ? 'active' : '' }}"
                                   href="{{ route('admin.social.index') }}">
                                   Social Setting
                               </a>
                           </li>
                           <li>
                               <a class="{{ request()->routeIs('admin.dynamic_page.*') ? 'active' : '' }}"
                                   href="{{ route('admin.dynamic_page.index') }}">
                                   Dynamic Page
                               </a>
                           </li>
                       </ul>
                   </li>
                   {{--
                   <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a class="sidebar-link sidebar-title"
                           href="#">
                           <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                               fill="none" stroke="#fefbfb" stroke-width="1" stroke-linecap="round"
                               stroke-linejoin="round">
                               <path
                                   d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
                               <path d="M12 4l0 16" />
                           </svg>


                           <span class="lan-7">Page layout</span></a>
                       <ul class="sidebar-submenu">
                           <li><a href="box-layout.html">Boxed</a></li>
                           <li><a href="layout-rtl.html">RTL</a></li>
                           <li><a href="layout-dark.html">Dark Layout</a></li>
                           <li> <a href="hide-on-scroll.html">Hide Nav Scroll</a></li>
                       </ul>
                   </li> --}}
                   <li class="sidebar-main-title">
                       <div>
                           <h6 class="lan-8">Applications</h6>
                       </div>
                   </li>
                   <li class="sidebar-list">
                       <i class="fa fa-thumb-tack"></i>
                       <a class="sidebar-link sidebar-title {{ request()->routeIs('admin.category.*') ? 'active' : '' }}"
                           href="#">
                           <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                               fill="none" stroke="#ffffff" stroke-width="1" stroke-linecap="round"
                               stroke-linejoin="round">
                               <path d="M4 4h6v6h-6z" />
                               <path d="M14 4h6v6h-6z" />
                               <path d="M4 14h6v6h-6z" />
                               <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                           </svg>
                           <span>Categories</span>
                       </a>
                       <ul class="sidebar-submenu {{ request()->routeIs('admin.category.*') ? 'd-block' : '' }}">
                           <li>
                               <a class="{{ request()->routeIs('admin.category.index') ? 'active' : '' }}"
                                   href="{{ route('admin.category.index') }}">
                                   Category List
                               </a>
                           </li>
                           <li>
                               <a class="{{ request()->routeIs('admin.category.create') ? 'active' : '' }}"
                                   href="{{ route('admin.category.create') }}">
                                   Add Category
                               </a>
                           </li>
                       </ul>
                   </li>
                   <li class="sidebar-list">
                       <i class="fa fa-thumb-tack"></i>
                       <a class="sidebar-link sidebar-title link-nav {{ request()->routeIs('admin.feedback.*') ? 'active' : '' }}"
                           href="{{ route('admin.feedback.index') }}">
                           <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                               fill="none" stroke="#ffffff" stroke-width="1" stroke-linecap="round"
                               stroke-linejoin="round">
                               <path d="M8 9h8" />
                               <path d="M8 13h6" />
                               <path
                                   d="M18 4a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-5l-5 3v-3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12z" />
                           </svg>
                           <span>Feedback</span>
                       </a>
                   </li>
                   <li class="sidebar-list">
                       <i class="fa fa-thumb-tack"></i>
                       <a class="sidebar-link sidebar-title link-nav {{ request()->routeIs('admin.support.*') ? 'active' : '' }}"
                           href="{{ route('admin.support.index') }}">
                           <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                               fill="none" stroke="#ffffff" stroke-width="1" stroke-linecap="round"
                               stroke-linejoin="round">
                               <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                           </svg>
                           <span>Support Tickets</span>
                       </a>
                   </li>
               </ul>
               <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
           </div>
       </nav>
   </div>
   <!-- Page Sidebar Ends-->

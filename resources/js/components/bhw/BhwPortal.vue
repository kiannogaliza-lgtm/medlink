<template>
  <div class="flex h-[100dvh] bg-[#f3f6f9] font-sans overflow-hidden relative">
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div v-if="mobileMenuOpen"
         @click="mobileMenuOpen = false"
         class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs z-40 lg:hidden transition-opacity"></div>

    <!-- Sidebar -->
    <aside :class="['w-[260px] text-white flex flex-col h-full shrink-0 z-50 fixed inset-y-0 left-0 lg:static transition-transform duration-300 ease-in-out', mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']"
           style="background: linear-gradient(180deg, #0c2d48 0%, #0a2540 50%, #081d33 100%)">
      <div class="p-4 flex items-center justify-between min-h-[76px] border-b border-white/10">
        <div class="flex items-center gap-3">
          <img src="/images/logo.png" alt="MEDLINK Logo" class="w-14 h-14 rounded-2xl object-contain shrink-0 drop-shadow-xl">
          <div class="leading-tight">
            <div class="font-black tracking-wider text-lg text-white">MEDLINK</div>
            <div class="text-[10px] text-cyan-200 font-semibold tracking-wider uppercase">BHW DASHBOARD</div>
          </div>
        </div>
        <!-- Close Mobile Button -->
        <button @click="mobileMenuOpen = false" class="lg:hidden text-gray-400 hover:text-white p-1 rounded-lg">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <nav class="flex-1 py-4 flex flex-col gap-1 px-4 overflow-y-auto">
        <template v-for="(tab, index) in tabs" :key="tab.id">
          <!-- Section category header guide -->
          <div v-if="tab.sectionHeader"
               :class="['px-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-cyan-200/70', index !== 0 ? 'pt-4' : 'pt-1']">
            {{ tab.sectionHeader }}
          </div>

          <button @click="handleTabSelect(tab.id)"
                  :class="['flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 text-left w-full cursor-pointer', currentTab === tab.id ? 'text-white translate-x-0.5' : 'text-[#94a3b8] hover:bg-white/8 hover:text-[#e2e8f0] hover:translate-x-0.5']"
                  :style="currentTab === tab.id ? 'background:#2563eb; box-shadow:0 4px 12px rgba(37,99,235,0.35)' : ''">
            <div class="flex items-center gap-3 min-w-0">
              <span v-html="tab.icon" class="w-5 h-5 flex items-center justify-center shrink-0"></span>
              <span class="truncate">{{ tab.name }}</span>
            </div>
            <span v-if="tab.badge && tab.badge > 0"
                  class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-red-500 text-white shrink-0 ml-2 shadow-sm">
              {{ tab.badge }}
            </span>
          </button>
        </template>
      </nav>

      <div class="p-6 text-xs text-white/40 mb-2 leading-relaxed">
        Barangay Honrado<br>Health System v1.0
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden relative min-w-0">
      <!-- Header -->
      <header class="h-[64px] sm:h-[72px] bg-white flex items-center justify-between px-3 sm:px-8 shrink-0 relative z-10 border-b border-gray-100">
        <div class="flex items-center gap-3 min-w-0">
          <!-- Mobile Hamburger Button -->
          <button @click="mobileMenuOpen = true" class="lg:hidden p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none cursor-pointer" aria-label="Toggle Menu">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <div class="min-w-0">
            <h2 class="text-lg sm:text-2xl font-semibold text-[#1a5a89] tracking-tight truncate">{{ activeTabName }}</h2>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5 hidden sm:block truncate">Welcome back, {{ user.first_name }} {{ user.last_name }}</p>
          </div>
        </div>
        
        <div class="flex items-center gap-1.5 sm:gap-6 shrink-0">
          <!-- Mobile Search Toggle Button -->
          <button type="button"
                  id="bhw-mobile-search-toggle"
                  @click="mobileSearchOpen = !mobileSearchOpen"
                  aria-label="Search"
                  class="sm:hidden text-gray-500 hover:text-[#1a5a89] transition-colors p-2 bg-gray-50 rounded-full border border-gray-100 focus:outline-none cursor-pointer">
            <span class="material-icons-outlined block" style="font-size:20px;">{{ mobileSearchOpen ? 'close' : 'search' }}</span>
          </button>

          <div :class="[mobileSearchOpen ? 'block fixed left-3 right-3 top-[70px] z-50 bg-white p-2 rounded-2xl shadow-xl border border-gray-200 sm:static sm:p-0 sm:bg-transparent sm:shadow-none sm:border-0' : 'hidden sm:block relative']"
               id="bhw-search-container">
            <svg class="w-4 h-4 absolute left-5 sm:left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text"
                   v-model="portalSearchQuery"
                   @input="handleSearchInput"
                   placeholder="Search..." 
                   class="pl-11 pr-8 py-2 bg-white border border-gray-200 rounded-full text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#3ac1eb]/50 focus:border-[#3ac1eb] w-full sm:w-48 lg:w-72 transition-all">
            <button v-if="portalSearchQuery"
                    @click="clearPortalSearch"
                    class="absolute right-4 sm:right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            <!-- Search Results Dropdown -->
            <div v-if="showSearchResults"
                 class="absolute left-0 right-0 sm:right-auto mt-2 sm:w-96 max-w-[calc(100vw-1.5rem)] bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50">
              <div class="px-4 py-2.5 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between text-xs text-gray-500 font-semibold">
                <span>Search Results</span>
                <span class="text-[#1a5a89] font-bold">{{ searchResults.length }} found</span>
              </div>
              <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                <div v-if="isSearching" class="px-4 py-6 text-center text-gray-400">
                  <span class="material-icons-outlined animate-spin text-[#1a5a89] mb-1" style="font-size: 24px;">sync</span>
                  <p class="text-xs">Searching...</p>
                </div>
                <template v-else-if="searchResults.length > 0">
                  <button v-for="(item, idx) in searchResults"
                          :key="idx"
                          @click="selectSearchResult(item)"
                          class="w-full text-left px-4 py-3 hover:bg-blue-50/60 transition-colors group">
                    <div class="flex items-start gap-3">
                      <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#1a5a89] flex items-center justify-center shrink-0 mt-0.5 group-hover:bg-[#1a5a89] group-hover:text-white transition-colors">
                        <span class="material-icons-outlined" style="font-size: 18px;">{{ item.icon || 'search' }}</span>
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                          <p class="text-xs font-bold text-gray-800 truncate group-hover:text-[#1a5a89]">{{ item.title }}</p>
                          <span class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-medium shrink-0">{{ item.category }}</span>
                        </div>
                        <p class="text-[11px] text-gray-500 truncate mt-0.5">{{ item.subtitle }}</p>
                      </div>
                    </div>
                  </button>
                </template>
                <div v-else class="px-4 py-8 text-center text-gray-400">
                  <span class="material-icons-outlined mb-2 text-gray-300" style="font-size: 32px;">search_off</span>
                  <p class="text-xs font-semibold text-gray-600">No results found</p>
                  <p class="text-[11px] text-gray-400 mt-0.5">No match for "{{ portalSearchQuery }}"</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Notifications Dropdown (Just like Nurse Portal) -->
          <div class="relative" id="bhw-notification-dropdown-container">
            <button @click="toggleNotificationDropdown" class="relative text-gray-500 hover:text-[#1a5a89] transition-colors p-2 bg-gray-50 rounded-full border border-gray-100 focus:outline-none cursor-pointer">
              <span class="material-icons-outlined block" style="font-size:22px;">notifications</span>
              <span v-if="totalBadgeCount > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 border-2 border-white rounded-full text-[10px] text-white flex items-center justify-center font-bold">
                {{ totalBadgeCount > 9 ? '9+' : totalBadgeCount }}
              </span>
            </button>

            <!-- Dropdown Panel -->
            <div v-if="showNotificationDropdown"
                 class="fixed sm:absolute left-3 right-3 sm:left-auto sm:right-0 top-[70px] sm:top-auto sm:mt-3 sm:w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50 transform origin-top-right transition-all animate-in fade-in zoom-in-95 duration-150">
              <div class="px-5 py-3.5 border-b border-gray-100 bg-gray-50/70 flex justify-between items-center">
                <div class="flex items-center gap-2">
                  <span class="material-icons-outlined text-[#1a5a89]" style="font-size:18px;">notifications</span>
                  <h3 class="text-sm font-bold text-gray-800">Notifications</h3>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-semibold bg-blue-100 text-blue-700 py-0.5 px-2.5 rounded-full">
                    {{ totalBadgeCount }} New
                  </span>
                  <button v-if="totalBadgeCount > 0" @click="markAllAsRead" class="text-[11px] text-blue-600 hover:text-blue-800 font-medium cursor-pointer">
                    Mark read
                  </button>
                </div>
              </div>

              <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
                <!-- Operational Summary 2: My Assigned Services Pending Action -->
                <a v-if="assignedHomeServicesCount > 0"
                   href="#" @click.prevent="goToTab('services')"
                   class="block px-4 py-3.5 hover:bg-blue-50/60 transition-colors">
                  <div class="flex gap-3 items-start">
                    <div class="w-9 h-9 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 mt-0.5">
                      <span class="material-icons-outlined" style="font-size: 18px;">pending_actions</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between">
                        <p class="text-xs font-bold text-gray-800">Assigned Services</p>
                        <span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.2 rounded font-semibold">{{ assignedHomeServicesCount }}</span>
                      </div>
                      <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                        You have {{ assignedHomeServicesCount }} assigned home visit(s) to fulfill.
                      </p>
                    </div>
                  </div>
                </a>

                <!-- Operational Summary 3: Assigned Appointments by Nurse -->
                <a v-if="pendingAssignedSchedulesCount > 0"
                   href="#" @click.prevent="goToTab('dashboard')"
                   class="block px-4 py-3.5 hover:bg-blue-50/60 transition-colors">
                  <div class="flex gap-3 items-start">
                    <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 mt-0.5">
                      <span class="material-icons-outlined" style="font-size: 18px;">calendar_month</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between">
                        <p class="text-xs font-bold text-gray-800">Nurse Assigned Schedules</p>
                        <span class="text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.2 rounded font-semibold">{{ pendingAssignedSchedulesCount }}</span>
                      </div>
                      <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                        Nurse assigned {{ pendingAssignedSchedulesCount }} appointment(s) to you waiting for your confirmation.
                      </p>
                    </div>
                  </div>
                </a>

                <!-- Operational Summary 4: Pending Medicine Delivery Requests -->
                <a v-if="pendingMedicineRequestsCount > 0"
                   href="#" @click.prevent="goToTab('medicine-requests')"
                   class="block px-4 py-3.5 hover:bg-blue-50/60 transition-colors">
                  <div class="flex gap-3 items-start">
                    <div class="w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 mt-0.5">
                      <span class="material-icons-outlined" style="font-size: 18px;">local_shipping</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between">
                        <p class="text-xs font-bold text-gray-800">Medicine Delivery</p>
                        <span class="text-[10px] bg-red-100 text-red-700 px-1.5 py-0.2 rounded font-semibold">{{ pendingMedicineRequestsCount }}</span>
                      </div>
                      <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                        There are {{ pendingMedicineRequestsCount }} pending medicine delivery request(s) to review.
                      </p>
                    </div>
                  </div>
                </a>

                <!-- Operational Summary 5: Pending Patient Verifications -->
                <a v-if="pendingPatientVerificationsCount > 0"
                   href="#" @click.prevent="goToTab('dashboard')"
                   class="block px-4 py-3.5 hover:bg-blue-50/60 transition-colors">
                  <div class="flex gap-3 items-start">
                    <div class="w-9 h-9 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 mt-0.5">
                      <span class="material-icons-outlined" style="font-size: 18px;">how_to_reg</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between">
                        <p class="text-xs font-bold text-gray-800">Patient Verifications</p>
                        <span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.2 rounded font-semibold">{{ pendingPatientVerificationsCount }}</span>
                      </div>
                      <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                        There are {{ pendingPatientVerificationsCount }} pending resident registration(s) to verify.
                      </p>
                    </div>
                  </div>
                </a>

                <!-- Individual Notifications List -->
                <template v-if="localNotificationsList.length > 0">
                  <div v-for="notif in localNotificationsList.slice(0, 6)" :key="notif.id"
                       :class="['px-4 py-3 transition-colors', !notif.is_read ? 'bg-blue-50/40 hover:bg-blue-50/70' : 'hover:bg-gray-50']">
                    <div class="flex gap-3 items-start">
                      <div :class="['w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5', notif.type === 'success' ? 'bg-green-100 text-green-600' : (notif.type === 'error' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600')]">
                        <span class="material-icons-outlined" style="font-size: 16px;">
                          {{ notif.type === 'success' ? 'check_circle' : (notif.type === 'error' ? 'cancel' : 'info') }}
                        </span>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-gray-800 truncate">{{ notif.title }}</p>
                        <p class="text-xs text-gray-500 mt-0.5 leading-snug line-clamp-2">{{ notif.message }}</p>
                        <span class="text-[10px] text-gray-400 mt-1 block">{{ notif.time_ago || 'Recent' }}</span>
                      </div>
                      <span v-if="!notif.is_read" class="w-2 h-2 rounded-full bg-blue-500 shrink-0 mt-1.5"></span>
                    </div>
                  </div>
                </template>

                <!-- Empty State -->
                <div v-if="totalBadgeCount === 0 && localNotificationsList.length === 0" class="px-4 py-8 text-center text-gray-400">
                  <span class="material-icons-outlined mb-2 text-gray-300" style="font-size: 36px;">notifications_none</span>
                  <p class="text-xs font-semibold text-gray-600">You're all caught up!</p>
                  <p class="text-[11px] text-gray-400 mt-0.5">No pending tasks or unread notifications.</p>
                </div>
              </div>

              <!-- Footer View All -->
              <div class="p-2.5 bg-gray-50/80 border-t border-gray-100 text-center">
                <button @click="goToTab('notifications')" class="text-xs text-[#1a5a89] hover:text-[#0c2d48] font-bold transition-colors cursor-pointer">
                  View All Notifications &rarr;
                </button>
              </div>
            </div>
          </div>
          
          <div class="flex items-center gap-1.5 sm:gap-4 border-l border-gray-100 pl-2 sm:pl-6">
             <div class="text-right hidden md:block">
                <div class="text-xs sm:text-sm font-semibold text-gray-700">{{ user.first_name }} {{ user.last_name }}</div>
                <div class="text-[10px] sm:text-[11px] text-green-500 font-medium">Online</div>
             </div>
             <div class="w-8 h-8 sm:w-10 sm:h-10 bg-[#0c2d48] text-white rounded-full flex items-center justify-center font-bold shadow-md text-xs sm:text-sm overflow-hidden border-2 border-white shrink-0">
                <img v-if="user.profile_photo" :src="'/storage/' + user.profile_photo" alt="Profile" class="w-full h-full object-cover" />
                <svg v-else class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
             </div>
             <form ref="logoutFormRef" method="POST" action="/logout" class="sm:ml-2 sm:border-l border-gray-100 sm:pl-6">
               <input type="hidden" name="_token" :value="csrfToken">
               <button type="button" @click="showLogoutModal = true" class="text-gray-400 hover:text-red-500 transition-colors bg-gray-50 p-2 rounded-full border border-gray-100 cursor-pointer" title="Logout">
                 <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                 </svg>
               </button>
             </form>
          </div>
        </div>
      </header>
      
      <!-- Content Area -->
      <div class="flex-1 overflow-auto p-3 sm:p-6 lg:p-8 pb-20 sm:pb-8 relative z-0">
        <component :is="currentComponent" 
                   :user="user" 
                   :dashboard-data="dashboardData"
                   :my-assigned-services="myAssignedServices"
                   :recent-activities="recentActivities"
                   :walkin-store-url="walkinStoreUrl"
                   :recent-walkins="recentWalkins"
                   :medicines="medicines"
                   :pending-home-services="pendingHomeServices"
                   :accepted-home-services="acceptedHomeServices"
                   :assigned-home-services="assignedHomeServices"
                   :completed-home-services="completedHomeServices"
                   :report-data="reportData"
                   :notifications="localNotificationsList"
                   :mark-read-url="markReadUrl"
                   :bhw-confirm-schedule-url="bhwConfirmScheduleUrl"
                   :announcements="announcements"
                   :announcements-store-url="announcementsStoreUrl"
                   :medicine-requests="medicineRequests"
                   :medicine-returns="medicineReturns"
                   :nurse-accounts="nurseAccounts"
                   :create-nurse-url="createNurseUrl" />
      </div>

      <!-- Floating help button -->
      <button class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 w-11 h-11 sm:w-12 sm:h-12 bg-gray-800 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-gray-700 transition-colors z-40">
        <span class="font-bold">?</span>
      </button>
    </main>

    <!-- Logout & Account Action Confirmation Modal -->
    <div v-if="showLogoutModal" id="logoutConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4" @click.self="closeLogoutModal">
      <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-sm overflow-hidden">
        <!-- Step 1: Confirm Logout + Account Options -->
        <template v-if="accountModalMode === 'logout'">
          <div class="p-6 text-center">
            <div class="w-14 h-14 mx-auto rounded-full bg-red-50 text-red-600 flex items-center justify-center mb-4">
              <span class="material-icons-outlined" style="font-size:28px;">logout</span>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Confirm Logout</h3>
            <p class="text-sm text-gray-500 mt-1.5">Are you sure you want to log out of your BHW account?</p>
          </div>
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2.5">
            <button type="button" @click="closeLogoutModal" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 transition-colors cursor-pointer">
              Cancel
            </button>
            <button type="button" @click="confirmLogout" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm transition-colors cursor-pointer">
              Confirm Logout
            </button>
          </div>
          <div class="px-6 py-3.5 bg-white border-t border-gray-100">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 text-center mb-2.5">Account Options</p>
            <div class="grid grid-cols-2 gap-2">
              <button type="button" @click="switchAccountModalMode('deactivate')" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-amber-800 bg-amber-50 hover:bg-amber-100 transition-colors cursor-pointer">
                <span class="material-icons-outlined" style="font-size:16px;">person_off</span>
                <span>Deactivate</span>
              </button>
              <button type="button" @click="switchAccountModalMode('delete')" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 transition-colors cursor-pointer">
                <span class="material-icons-outlined" style="font-size:16px;">delete_forever</span>
                <span>Delete Account</span>
              </button>
            </div>
          </div>
        </template>

        <!-- Step 2: Deactivate / Delete Confirmation -->
        <template v-else>
          <div class="p-6">
            <div class="flex items-start gap-3">
              <div :class="accountModalMode === 'delete' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600'" class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0">
                <span class="material-icons-outlined" style="font-size:22px;">{{ accountModalMode === 'delete' ? 'delete_forever' : 'person_off' }}</span>
              </div>
              <div>
                <h3 class="text-base font-bold text-gray-900">
                  {{ accountModalMode === 'delete' ? 'Delete Account Permanently?' : 'Deactivate Your Account?' }}
                </h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                  {{ accountModalMode === 'delete'
                    ? 'This permanently deletes your BHW account, logs the event in Account History, and signs you out immediately.'
                    : 'Your BHW account will be set to inactive, logged in Account History, and you will be signed out until reactivated by a Nurse.' }}
                </p>
              </div>
            </div>

            <div v-if="accountActionError" class="mt-4 p-3 rounded-xl bg-red-50 text-xs text-red-700 font-semibold">
              {{ accountActionError }}
            </div>

            <form @submit.prevent="submitAccountAction" class="mt-4 space-y-3">
              <div>
                <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">Confirm Current Password *</label>
                <input v-model="accountActionPassword" type="password" required placeholder="Enter your password"
                       class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30">
              </div>
              <div>
                <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">Reason (Optional)</label>
                <textarea v-model="accountActionReason" rows="2" maxlength="500" placeholder="Optional reason..."
                          class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 resize-none"></textarea>
              </div>
              <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" @click="accountModalMode = 'logout'" :disabled="accountActionLoading" class="px-3.5 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">
                  Back
                </button>
                <button type="submit" :disabled="accountActionLoading"
                        :class="accountModalMode === 'delete' ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700'"
                        class="px-4 py-2 text-xs font-semibold text-white rounded-xl shadow-sm transition-colors cursor-pointer disabled:opacity-50">
                  <span v-if="accountActionLoading">Processing...</span>
                  <span v-else>{{ accountModalMode === 'delete' ? 'Yes, Delete Account' : 'Yes, Deactivate' }}</span>
                </button>
              </div>
            </form>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import BhwDashboard from './BhwDashboard.vue';
import WalkinRegistration from './WalkinRegistration.vue';
import ViewInventory from './ViewInventory.vue';
import HomeServices from './HomeServices.vue';
import Reports from './Reports.vue';
import Notifications from '../resident/Notifications.vue';
import BhwAnnouncements from './BhwAnnouncements.vue';
import MedicineDeliveryRequests from './MedicineDeliveryRequests.vue';
import ReturnedMedicines from './ReturnedMedicines.vue';
import NurseManagement from './NurseManagement.vue';

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  dashboardData: {
    type: Object,
    default: () => ({})
  },
  myAssignedServices: {
    type: Array,
    default: () => []
  },
  recentActivities: {
    type: Array,
    default: () => []
  },
  walkinStoreUrl: {
    type: String,
    default: ''
  },
  recentWalkins: {
    type: Array,
    default: () => []
  },
  medicines: {
    type: Array,
    default: () => []
  },
  pendingHomeServices: {
    type: Array,
    default: () => []
  },
  acceptedHomeServices: {
    type: Array,
    default: () => []
  },
  assignedHomeServices: {
    type: Array,
    default: () => []
  },
  completedHomeServices: {
    type: Array,
    default: () => []
  },
  reportData: {
    type: Object,
    default: () => ({})
  },
  notifications: {
    type: Array,
    default: () => []
  },
  notificationSummary: {
    type: Object,
    default: () => ({})
  },
  markReadUrl: {
    type: String,
    default: ''
  },
  bhwConfirmScheduleUrl: {
    type: String,
    default: ''
  },
  announcements: {
    type: Array,
    default: () => []
  },
  announcementsStoreUrl: {
    type: String,
    default: '/announcements'
  },
  medicineRequests: {
    type: Array,
    default: () => []
  },
  medicineReturns: {
    type: Array,
    default: () => []
  },
  nurseAccounts: {
    type: Array,
    default: () => []
  },
  createNurseUrl: {
    type: String,
    default: '/bhw/nurses'
  }
});

// ── Notification dropdown state ──
const showNotificationDropdown = ref(false);
const localNotificationsList = ref([]);
const mobileMenuOpen = ref(false);
const mobileSearchOpen = ref(false);
const showLogoutModal = ref(false);
const logoutFormRef = ref(null);
const accountModalMode = ref('logout');
const accountActionPassword = ref('');
const accountActionReason = ref('');
const accountActionLoading = ref(false);
const accountActionError = ref('');

function closeLogoutModal() {
  if (accountActionLoading.value) return;
  showLogoutModal.value = false;
  accountModalMode.value = 'logout';
  accountActionPassword.value = '';
  accountActionReason.value = '';
  accountActionError.value = '';
}

function switchAccountModalMode(mode) {
  accountModalMode.value = mode;
  accountActionPassword.value = '';
  accountActionReason.value = '';
  accountActionError.value = '';
}

async function submitAccountAction() {
  accountActionLoading.value = true;
  accountActionError.value = '';
  const isDelete = accountModalMode.value === 'delete';

  try {
    const res = await fetch(isDelete ? '/account/delete' : '/account/deactivate', {
      method: isDelete ? 'DELETE' : 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken.value,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        password: accountActionPassword.value,
        reason: accountActionReason.value,
      }),
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      accountActionError.value = data.message || 'Incorrect password or unable to process request.';
      return;
    }
    try { sessionStorage.removeItem('medlink_bhw_tab'); } catch (e) {}
    window.location.href = data.redirect || '/login';
  } catch (err) {
    accountActionError.value = 'Network error. Please try again.';
  } finally {
    accountActionLoading.value = false;
  }
}

function confirmLogout() {
  try { sessionStorage.removeItem('medlink_bhw_tab'); } catch (e) {}
  if (logoutFormRef.value) {
    logoutFormRef.value.submit();
  }
}

function handleTabSelect(tabId) {
  currentTab.value = tabId;
  try { sessionStorage.setItem('medlink_bhw_tab', tabId); } catch (e) {}
  mobileMenuOpen.value = false;
}

function handleNewRealtimeNotification(e) {
  if (!e.detail) return;
  const newNotif = e.detail;
  // If not already in list, prepend it to localNotificationsList
  if (!localNotificationsList.value.some(n => n.id === newNotif.id)) {
    localNotificationsList.value.unshift(newNotif);
    // Un-dismiss categories so unread indicators reactivate immediately
    dismissedCategories.value = false;
  }
}

function handleNotificationsReadLocally() {
  dismissedCategories.value = true;
  localNotificationsList.value = localNotificationsList.value.map(n => ({ ...n, is_read: true }));
}

onMounted(() => {
  const tokenMeta = document.querySelector('meta[name="csrf-token"]');
  if (tokenMeta) {
    csrfToken.value = tokenMeta.getAttribute('content');
  }
  // Initialise the local notifications list from the prop
  localNotificationsList.value = [...props.notifications];
  // Close dropdown when clicking outside
  document.addEventListener('click', handleClickOutside);
  window.addEventListener('notifications-read', handleNotificationsReadLocally);
  window.addEventListener('medlink-new-notification', handleNewRealtimeNotification);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  window.removeEventListener('notifications-read', handleNotificationsReadLocally);
  window.removeEventListener('medlink-new-notification', handleNewRealtimeNotification);
});

function handleClickOutside(e) {
  const container = document.getElementById('bhw-notification-dropdown-container');
  if (container && !container.contains(e.target)) {
    showNotificationDropdown.value = false;
  }
  const searchContainer = document.getElementById('bhw-search-container');
  const mobileSearchBtn = document.getElementById('bhw-mobile-search-toggle');
  if (searchContainer && !searchContainer.contains(e.target)) {
    showSearchResults.value = false;
    if (!mobileSearchBtn || !mobileSearchBtn.contains(e.target)) {
      mobileSearchOpen.value = false;
    }
  }
}

// ── Search functionality ──
const portalSearchQuery = ref('');
const showSearchResults = ref(false);
const searchResults = ref([]);
const isSearching = ref(false);
let searchDebounceTimer = null;

function handleSearchInput() {
  const q = portalSearchQuery.value.trim();
  if (!q) {
    showSearchResults.value = false;
    searchResults.value = [];
    return;
  }
  showSearchResults.value = true;
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    performGlobalSearch(q);
  }, 250);
}

function clearPortalSearch() {
  portalSearchQuery.value = '';
  showSearchResults.value = false;
  searchResults.value = [];
}

async function performGlobalSearch(q) {
  isSearching.value = true;
  try {
    const res = await fetch(`/api/search?q=${encodeURIComponent(q)}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    if (res.ok) {
      const data = await res.json();
      searchResults.value = data.results || [];
    }
  } catch (err) {
    console.error('BHW global search error', err);
    searchResults.value = [];
  } finally {
    isSearching.value = false;
  }
}

function selectSearchResult(item) {
  if (item.tab) {
    goToTab(item.tab);
  }
  showSearchResults.value = false;
}

function toggleNotificationDropdown() {
  showNotificationDropdown.value = !showNotificationDropdown.value;
}

// ── Operational notification counts (from notificationSummary prop) ──
const dismissedCategories = ref(false);

const pendingHomeServicesCount = computed(() => dismissedCategories.value ? 0 : (props.notificationSummary?.pendingHomeServices || 0));
const assignedHomeServicesCount = computed(() => dismissedCategories.value ? 0 : (props.notificationSummary?.assignedHomeServices || 0));
const pendingAssignedSchedulesCount = computed(() => dismissedCategories.value ? 0 : (props.notificationSummary?.pendingAssignedSchedules || 0));
const pendingMedicineRequestsCount = computed(() => dismissedCategories.value ? 0 : (props.notificationSummary?.pendingMedicineRequests || 0));
const pendingPatientVerificationsCount = computed(() => dismissedCategories.value ? 0 : (props.notificationSummary?.pendingPatientVerifications || 0));

const unreadCount = computed(() => {
  return localNotificationsList.value.filter(n => !n.is_read).length;
});

const totalBadgeCount = computed(() => {
  return assignedHomeServicesCount.value
       + pendingAssignedSchedulesCount.value
       + pendingMedicineRequestsCount.value
       + pendingPatientVerificationsCount.value
       + unreadCount.value;
});

// ── Mark all personal notifications as read ──
async function markAllAsRead() {
  dismissedCategories.value = true;
  if (!props.markReadUrl) {
    localNotificationsList.value = localNotificationsList.value.map(n => ({ ...n, is_read: true }));
    return;
  }
  try {
    const res = await fetch(props.markReadUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken.value,
      },
      body: JSON.stringify({}),
    });
    if (res.ok) {
      localNotificationsList.value = localNotificationsList.value.map(n => ({ ...n, is_read: true }));
    }
  } catch (e) {
    console.error('Failed to mark notifications as read', e);
  }
}

// ── Navigate to a sidebar tab ──
function goToTab(tabId) {
  currentTab.value = tabId;
  try { sessionStorage.setItem('medlink_bhw_tab', tabId); } catch (e) {}
  showNotificationDropdown.value = false;
}

const csrfToken = ref('');

const canManageNurses = computed(() => Boolean(props.user?.can_manage_nurses));

const savedBhwTab = (() => {
  try {
    const t = sessionStorage.getItem('medlink_bhw_tab') || 'dashboard';
    if (t === 'nurses' && !props.user?.can_manage_nurses) return 'dashboard';
    return t;
  } catch (e) { return 'dashboard'; }
})();
const currentTab = ref(savedBhwTab);

const tabs = computed(() => {
  const list = [
    { id: 'dashboard', name: 'Dashboard', sectionHeader: 'Overview', icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>', component: BhwDashboard },
    { id: 'walkin', name: 'Walk-in Registration', icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>', component: WalkinRegistration },
    { id: 'inventory', name: 'View Inventory', sectionHeader: 'Medicine & Inventory', icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>', component: ViewInventory },
    { id: 'returned-medicines', name: 'Returned Medicines', icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 15v-1a4 4 0 00-4-4H4m0 0l3-3m-3 3l3 3m5 4v1a3 3 0 003 3h4a3 3 0 003-3V7a3 3 0 00-3-3h-4a3 3 0 00-3 3v1" /></svg>', component: ReturnedMedicines },
    { id: 'medicine-requests', name: 'Medicine Delivery', badge: pendingMedicineRequestsCount.value, icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m-4-4h8" /></svg>', component: MedicineDeliveryRequests },
    { id: 'services', name: 'Home Services', badge: assignedHomeServicesCount.value, sectionHeader: 'Services & Community', icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>', component: HomeServices },
    { id: 'announcements', name: 'Schedule Announcements', icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>', component: BhwAnnouncements },
    { id: 'reports', name: 'Reports', sectionHeader: 'Administration', icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>', component: Reports },
  ];

  if (canManageNurses.value) {
    list.push({ id: 'nurses', name: 'Nurse Accounts', icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>', component: NurseManagement });
  }

  list.push({ id: 'notifications', name: 'Notifications', badge: unreadCount.value, icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>', component: Notifications });

  return list;
});

const currentComponent = computed(() => {
  return tabs.value.find(t => t.id === currentTab.value)?.component || BhwDashboard;
});

const activeTabName = computed(() => {
  if (currentTab.value === 'dashboard') {
      return 'BHW Dashboard';
  }
  if (currentTab.value === 'walkin') {
      return 'Walk-in Patient Registration';
  }
  if (currentTab.value === 'inventory') {
      return 'Medicine Inventory';
  }
  if (currentTab.value === 'returned-medicines') {
      return 'Returned Medicines';
  }
  if (currentTab.value === 'services') {
      return 'Home Service Management';
  }
  if (currentTab.value === 'medicine-requests') {
      return 'Medicine Delivery Requests';
  }
  if (currentTab.value === 'reports') {
      return 'My Reports';
  }
  if (currentTab.value === 'nurses') {
      return 'Nurse Account Management';
  }
  if (currentTab.value === 'announcements') {
      return 'Schedule Announcements';
  }
  if (currentTab.value === 'notifications') {
      return 'Notifications';
  }
  return tabs.value.find(t => t.id === currentTab.value)?.name || 'BHW Dashboard';
});
</script>


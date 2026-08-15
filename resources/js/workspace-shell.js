/**
 * Shared chrome behavior for the four operator workspaces (admin/vendor/
 * employee/syndicate): theme toggle, mobile sidebar toggle, logout, and the
 * notification bell dropdown. Each layout's inline script still owns its own
 * auth-bootstrap/hydrate block (role-specific `type` check + resource
 * loading), but calls into this module for everything that was previously
 * copy-pasted four times with only id-prefix substitutions.
 */
(function () {
    function esc(value) {
        if (value == null) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = String(value);
        return div.innerHTML;
    }

    function deleteCookie(name, path = '/', domain = '') {
        let cookieString = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=' + path;
        if (domain) {
            cookieString += '; domain=' + domain;
        }
        document.cookie = cookieString;
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=' + path;
    }

    function toggleTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('sz_theme', isDark ? 'dark' : 'light');
        return isDark;
    }

    function initSidebarToggle(options) {
        options = options || {};
        const sidebar = document.getElementById(options.sidebarId || 'admin-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const toggle = document.getElementById('sidebar-toggle');
        const hiddenClass = options.hiddenClass;

        function close() {
            sidebar?.classList.add(hiddenClass);
            backdrop?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
        }

        function open() {
            sidebar?.classList.remove(hiddenClass);
            backdrop?.classList.remove('hidden');
            document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
        }

        toggle?.addEventListener('click', open);
        backdrop?.addEventListener('click', close);
        window.closeSidebar = close;
        return { open, close };
    }

    async function logout(loginUrl) {
        try {
            // Always call the real logout endpoint, not just when a bearer
            // token is present in localStorage. The four operator
            // workspaces (admin/vendor/employee/syndicate) authenticate via
            // Sanctum's session-cookie guard, not a bearer token, so `token`
            // here is normally null for them — the old token-gated call
            // meant this endpoint was never actually reached for those
            // users, leaving their server-side session alive after
            // "logout" (the client redirected to /login?logout=1, but
            // auth()->check() was still true server-side). AuthController
            // @logout revokes any Sanctum token AND destroys the web
            // session; axios already sends the session cookie + CSRF token
            // (withCredentials/withXSRFToken in bootstrap.js), and
            // localhost/the app's own domain is a configured Sanctum
            // stateful domain, so this authenticates correctly either way.
            if (window.axios) {
                try {
                    await window.axios.post('/api/auth/logout');
                } catch (error) {
                    console.log('Logout API call failed (continuing):', error);
                }
            }

            if (window.Auth?.clearAll) {
                window.Auth.clearAll();
            } else {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('auth_user');
                sessionStorage.clear();
                if (window.axios) {
                    delete window.axios.defaults.headers.common.Authorization;
                }
            }

            deleteCookie('XSRF-TOKEN');
            deleteCookie('laravel_session');
        } catch (error) {
            console.error('Error during logout:', error);
        } finally {
            window.location.replace(loginUrl + '?logout=1');
        }
    }

    /**
     * Generic accessible dropdown wiring: aria-expanded, outside-click and
     * Escape to close, closes sibling open panels. Used by both the
     * notification bell and any .row-actions-menu kebab trigger.
     */
    function wireDropdown(trigger, panel, onOpen) {
        if (!trigger || !panel) {
            return null;
        }

        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-haspopup', 'true');

        function close() {
            panel.classList.add('hidden');
            trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', function (event) {
            event.stopPropagation();
            const wasHidden = panel.classList.contains('hidden');
            document.querySelectorAll('.row-actions-panel, .dropdown-panel').forEach(function (openPanel) {
                if (openPanel !== panel) {
                    openPanel.classList.add('hidden');
                }
            });
            document.querySelectorAll('[aria-expanded="true"]').forEach(function (openTrigger) {
                if (openTrigger !== trigger) {
                    openTrigger.setAttribute('aria-expanded', 'false');
                }
            });
            panel.classList.toggle('hidden', !wasHidden);
            trigger.setAttribute('aria-expanded', String(wasHidden));
            if (wasHidden && typeof onOpen === 'function') {
                onOpen();
            }
        });

        document.addEventListener('click', function (event) {
            if (!panel.contains(event.target) && !trigger.contains(event.target)) {
                close();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                close();
            }
        });

        return { close };
    }

    const NOTIFICATION_LINKS = {
        admin: { product: '/admin/products/', order: '/admin/orders/' },
        vendor: { product: '/products/', order: '/vendor/orders/' },
    };

    function resolveNotificationLink(context, actionType, actionId) {
        if (!actionType || actionId == null) {
            return '';
        }
        const map = NOTIFICATION_LINKS[context] || { product: '/products/', order: '/orders/' };
        const path = map[actionType];
        return path ? path + String(actionId) : '';
    }

    /**
     * Wires the notification bell + dropdown for a given workspace context
     * ('admin' | 'vendor'). No-ops entirely if the DOM elements for this
     * context aren't present, so calling it from a layout that doesn't have
     * a notifications feature (employee, syndicate) is always safe.
     */
    function initNotifications(context, strings) {
        strings = strings || {};
        const btn = document.getElementById(context + '-notif-btn');
        const dropdown = document.getElementById(context + '-notif-dropdown');
        const badge = document.getElementById(context + '-notif-badge');
        const listEl = document.getElementById(context + '-notif-list');
        const emptyEl = document.getElementById(context + '-notif-empty');
        const paginationEl = document.getElementById(context + '-notif-pagination');
        const pageInfoEl = document.getElementById(context + '-notif-page-info');
        const prevBtn = document.getElementById(context + '-notif-prev');
        const nextBtn = document.getElementById(context + '-notif-next');
        const markAllBtn = document.getElementById(context + '-notif-mark-all');

        if (!btn || !dropdown || !listEl) {
            return { refreshBadge: function () {} };
        }

        let currentPage = 1;

        function refreshBadge() {
            window.Auth?.applyToken?.();
            window.axios.get('/api/notifications', { params: { per_page: 1 } }).then(function (response) {
                const count = (response.data && response.data.unread_count) || 0;
                if (!badge) {
                    return;
                }
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }).catch(function () {});
        }

        function setPaginationDisabled(disabled, lastPage) {
            if (prevBtn) {
                prevBtn.disabled = disabled || currentPage <= 1;
            }
            if (nextBtn) {
                nextBtn.disabled = disabled || currentPage >= (lastPage || 1);
            }
        }

        function load(page) {
            page = typeof page === 'number' && page >= 1 ? page : 1;
            listEl.innerHTML = '<p class="px-4 py-10 text-center text-[13px] text-gray-400 dark:text-gray-500">' + esc(strings.loading || 'Loading...') + '</p>';
            emptyEl?.classList.add('hidden');
            paginationEl?.classList.add('hidden');
            window.Auth?.applyToken?.();

            window.axios.get('/api/notifications', { params: { page } }).then(function (response) {
                const data = response.data || {};
                const items = Array.isArray(data.data) ? data.data : [];
                const meta = data.meta || {};
                currentPage = meta.current_page || 1;
                const lastPage = meta.last_page || 1;
                const total = meta.total || 0;
                const unread = data.unread_count ?? 0;

                if (badge) {
                    if (unread > 0) {
                        badge.textContent = unread > 99 ? '99+' : unread;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }

                if (items.length === 0 && page === 1) {
                    listEl.innerHTML = '';
                    emptyEl?.classList.remove('hidden');
                    return;
                }
                emptyEl?.classList.add('hidden');

                listEl.innerHTML = items.map(function (notification) {
                    const isUnread = !notification.read_at;
                    let time = '';
                    try {
                        if (notification.sent_at) {
                            time = new Date(notification.sent_at).toLocaleDateString(undefined, { dateStyle: 'short', timeStyle: 'short' });
                        }
                    } catch (error) {}
                    const body = notification.body != null ? esc(String(notification.body)) : '';
                    const sender = notification.sender_name != null ? esc(String(notification.sender_name)) : '';
                    const href = resolveNotificationLink(context, notification.action_type, notification.action_id != null ? notification.action_id : null);
                    const clickable = href ? ' cursor-pointer' : '';
                    const dataHref = href ? ' data-href="' + esc(href) + '"' : '';
                    const id = notification.id != null ? String(notification.id) : '';
                    return '<div class="flex border-b border-gray-100 last:border-0 dark:border-gray-800 ' + (isUnread ? 'bg-gray-50/60 dark:bg-gray-800/40' : 'hover:bg-gray-50/50 dark:hover:bg-gray-800/30') + clickable + '" data-nid="' + id + '"' + dataHref + ' role="' + (href ? 'button' : 'presentation') + '">' +
                        (isUnread ? '<div class="w-0.5 shrink-0 self-stretch bg-brand-500 dark:bg-brand-400" aria-hidden="true"></div>' : '') +
                        '<div class="min-w-0 flex-1 px-4 py-3.5 ' + (isUnread ? 'ps-3.5' : 'ps-4') + '">' +
                        '<p class="text-[14px] leading-relaxed text-gray-800 dark:text-gray-100">' + body + '</p>' +
                        '<p class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">' + time + (sender ? ' – ' + sender : '') + '</p>' +
                        (isUnread ? '<button type="button" class="ws-notif-mark-one mt-2 text-[11px] font-medium text-brand-600 hover:underline dark:text-brand-400" data-id="' + id + '">' + esc(strings.markOneRead || 'Mark as read') + '</button>' : '') +
                        '</div></div>';
                }).join('');

                if (lastPage > 1 && paginationEl && pageInfoEl && prevBtn && nextBtn) {
                    pageInfoEl.textContent = esc(strings.page || 'Page') + ' ' + currentPage + ' ' + esc(strings.of || 'of') + ' ' + lastPage + (total ? ' (' + total + ')' : '');
                    setPaginationDisabled(false, lastPage);
                    prevBtn.onclick = function () { if (currentPage > 1) load(currentPage - 1); };
                    nextBtn.onclick = function () { if (currentPage < lastPage) load(currentPage + 1); };
                    paginationEl.classList.remove('hidden');
                }

                listEl.querySelectorAll('.ws-notif-mark-one').forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.stopPropagation();
                        event.preventDefault();
                        const id = button.getAttribute('data-id');
                        if (id) {
                            window.axios.patch('/api/notifications/' + id + '/read').then(function () {
                                load(currentPage);
                            });
                        }
                    });
                });

                listEl.querySelectorAll('[data-nid][data-href]').forEach(function (row) {
                    row.addEventListener('click', function (event) {
                        if (event.target.closest('.ws-notif-mark-one')) {
                            return;
                        }
                        const href = row.getAttribute('data-href');
                        if (href) {
                            dropdown.classList.add('hidden');
                            window.location.href = href;
                        }
                    });
                });
            }).catch(function (error) {
                const message = (error.response && error.response.status === 401)
                    ? (strings.signInAgain || 'Please sign in again.')
                    : (error.response?.data?.message || strings.failed || 'Failed to load.');
                listEl.innerHTML = '<p class="px-4 py-6 text-center text-sm text-red-500">' + esc(message) + '</p>';
            }).finally(function () {
                setPaginationDisabled(false);
            });
        }

        wireDropdown(btn, dropdown, function () {
            load(1);
        });

        markAllBtn?.addEventListener('click', function () {
            markAllBtn.disabled = true;
            window.Auth?.applyToken?.();
            window.axios.post('/api/notifications/mark-all-read').then(function () {
                load(1);
                refreshBadge();
            }).finally(function () {
                markAllBtn.disabled = false;
            });
        });

        refreshBadge();

        return { refreshBadge, load };
    }

    /**
     * Wires a modal/dialog element with role="dialog", a focus trap, and
     * Escape-to-close. Call .open() right after showing the modal and
     * .close() right before hiding it. Previously duplicated identically in
     * layouts/admin.blade.php and layouts/vendor.blade.php; also called
     * directly (as window.wireAccessibleDialog) by several admin list pages'
     * delete-confirmation modals, so it stays a global, not just a
     * VetoraWorkspace member.
     */
    window.wireAccessibleDialog = function (modal, closeFn, options) {
        options = options || {};
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        if (options.labelledBy) {
            modal.setAttribute('aria-labelledby', options.labelledBy);
        }

        let previouslyFocused = null;

        function focusableElements() {
            return Array.from(modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
                .filter((el) => !el.disabled && el.offsetParent !== null);
        }

        function onKeydown(event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeFn();
                return;
            }

            if (event.key === 'Tab') {
                const focusable = focusableElements();
                if (!focusable.length) {
                    return;
                }
                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        }

        return {
            open: function () {
                previouslyFocused = document.activeElement;
                document.addEventListener('keydown', onKeydown);
                const focusable = focusableElements();
                (focusable[0] || modal).focus();
            },
            close: function () {
                document.removeEventListener('keydown', onKeydown);
                if (previouslyFocused && previouslyFocused.focus) {
                    previouslyFocused.focus();
                }
            },
        };
    };

    window.VetoraWorkspace = {
        esc,
        deleteCookie,
        toggleTheme,
        initSidebarToggle,
        logout,
        wireDropdown,
        initNotifications,
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    // State management
    let projects = [];
    let endpoints = [];
    let currentEndpointIndex = -1;
    let selectedProjectId = null;
    const currentUserRole = document.getElementById('user-role-data').value;

    // Elements
    const endpointList = document.getElementById('endpoint-list');
    const requestMethod = document.getElementById('request-method');
    const requestUrl = document.getElementById('request-url');
    const sendBtn = document.getElementById('send-request-btn');
    const responseOutput = document.getElementById('response-output');
    const responseMeta = document.getElementById('response-meta');
    const responseStatus = document.getElementById('response-status');
    const responseTime = document.getElementById('response-time');
    const responseSize = document.getElementById('response-size');
    const addEndpointBtn = document.getElementById('add-endpoint-btn');
    const createProjectBtn = document.getElementById('create-project-btn');
    const saveCollectionBtn = document.getElementById('save-collection-btn');
    const manageUsersBtn = document.getElementById('manage-users-btn');
    const topBurgerBtn = document.getElementById('top-burger-btn');
    const topBurgerDropdown = document.getElementById('top-burger-dropdown');
    // Modals
    const saveModal = document.getElementById('save-endpoint-modal');
    const projectModal = document.getElementById('create-project-modal');
    const userModal = document.getElementById('user-management-modal');
    const editUserModal = document.getElementById('edit-user-modal');
    const myProfileBtn = document.getElementById('my-profile-btn');
    const profileModal = document.getElementById('profile-modal');
    const viewLogsBtn = document.getElementById('view-logs-btn');
    const logsModal = document.getElementById('logs-modal');

    // Environments State & Elements
    let environments = [];
    const envSelector = document.getElementById('env-selector');
    const manageEnvsBtn = document.getElementById('manage-envs-btn');
    const envsModal = document.getElementById('envs-modal');
    const envEditSection = document.getElementById('env-edit-section');
    const envItemsList = document.getElementById('env-items-list');

    // Helper for Headers
    function getHeaders() {
        return {
            'Content-Type': 'application/json'
        };
    }

    // Modal Control
    window.closeModal = (id) => {
        document.getElementById(id).style.display = 'none';
    };

    // Tab Logic
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const target = link.getAttribute('data-tab');
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.add('hidden'));
            link.classList.add('active');
            document.getElementById(`${target}-tab`).classList.remove('hidden');
        });
    });

    // Body Type Logic
    const bodyRadios = document.querySelectorAll('input[name="body-type"]');
    const bodyRawContainer = document.getElementById('body-raw-container');
    const bodyFormContainer = document.getElementById('body-form-container');
    bodyRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            bodyRawContainer.classList.add('hidden');
            bodyFormContainer.classList.add('hidden');
            if (e.target.value === 'raw') bodyRawContainer.classList.remove('hidden');
            if (e.target.value === 'form-data') bodyFormContainer.classList.remove('hidden');
        });
    });

    // Key-Value Editor Logic
    window.createRow = (containerId, key = '', value = '') => {
        const container = document.getElementById(containerId);
        const row = document.createElement('div');
        row.className = 'key-value-row';
        row.innerHTML = `
            <input type="text" placeholder="Key" class="kv-key" value="${key}">
            <input type="text" placeholder="Value" class="kv-value" value="${value}">
            <button class="icon-btn remove-row-btn"><i class="fas fa-times"></i></button>
        `;
        row.querySelector('.remove-row-btn').addEventListener('click', () => row.remove());
        container.appendChild(row);
    }

    document.querySelectorAll('.add-row-btn').forEach(btn => {
        btn.addEventListener('click', () => createRow(btn.getAttribute('data-target')));
    });

    // Initial Editor Rows
    createRow('params-editor');
    createRow('headers-editor');
    createRow('body-form-editor');

    // API Calls
    async function loadProjects() {
        const res = await fetch('api.php?action=projects');
        projects = await res.json();
        const select = document.getElementById('save-project-id');
        select.innerHTML = projects.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
    }

    async function loadEndpoints() {
        const res = await fetch('api.php?action=endpoints');
        endpoints = await res.json();
        updateCategoryDatalist();
        renderSidebar();
    }

    function updateCategoryDatalist(projectId = null) {
        const datalist = document.getElementById('category-list');
        let filteredEps = endpoints;
        if (projectId) {
            filteredEps = endpoints.filter(ep => ep.project_id == projectId);
        }
        const categories = [...new Set(filteredEps.map(ep => ep.category).filter(c => c))];
        datalist.innerHTML = categories.map(cat => `<option value="${cat}">`).join('');
    }

    document.getElementById('save-project-id').addEventListener('change', (e) => {
        updateCategoryDatalist(e.target.value);
    });

    function renderSidebar() {
        endpointList.innerHTML = '';
        if (projects.length === 0) {
            endpointList.innerHTML = '<div class="empty-state" style="color:var(--text-dim); text-align:center; padding:20px;">No collections found.</div>';
            return;
        }

        projects.forEach(project => {
            const pDiv = document.createElement('div');
            pDiv.className = 'project-group';

            // Project Header with Menu
            const header = document.createElement('div');
            header.className = 'project-header';
            header.innerHTML = `
                <div class="project-title" style="display:flex; align-items:center; gap:10px; flex:1;">
                    <span>${project.name}</span>
                </div>
                <div class="project-menu-container">
                    <button class="project-menu-btn icon-btn">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu hidden" id="menu-${project.id}">
                        <div class="dropdown-item edit-project">
                            <i class="fas fa-edit"></i> Edit
                        </div>
                        ${currentUserRole === 'superadmin' ? `
                        <div class="dropdown-item danger delete-project">
                            <i class="fas fa-trash"></i> Delete
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;

            const menuBtn = header.querySelector('.project-menu-btn');
            const dropdown = header.querySelector('.dropdown-menu');

            menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== dropdown) m.classList.add('hidden');
                });
                dropdown.classList.toggle('hidden');
            });

            header.querySelector('.edit-project').addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.add('hidden');
                openEditProjectModal(project);
            });

            if (currentUserRole === 'superadmin') {
                header.querySelector('.delete-project').addEventListener('click', (e) => {
                    e.stopPropagation();
                    dropdown.classList.add('hidden');
                    if (confirm(`Are you sure you want to delete collection "${project.name}"? All endpoints in this collection will be removed.`)) {
                        deleteProject(project.id);
                    }
                });
            }

            pDiv.appendChild(header);

            // Filter endpoints for this project
            const projectEndpoints = endpoints.filter(e => e.project_id == project.id);

            // Group by category
            const categories = {};
            projectEndpoints.forEach(ep => {
                const cat = ep.category || 'Default';
                if (!categories[cat]) categories[cat] = [];
                categories[cat].push(ep);
            });

            for (const [catName, catsEps] of Object.entries(categories)) {
                const catDiv = document.createElement('div');
                catDiv.className = 'category-item';
                catDiv.innerHTML = `
                    <div class="category-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="category-name">${catName}</span>
                        <button class="edit-category-btn icon-btn" title="Rename Category" style="font-size:0.7rem; padding:2px; opacity:0; transition:opacity 0.2s;">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                `;

                const editCatBtn = catDiv.querySelector('.edit-category-btn');
                catDiv.addEventListener('mouseenter', () => editCatBtn.style.opacity = '0.5');
                catDiv.addEventListener('mouseleave', () => editCatBtn.style.opacity = '0');
                editCatBtn.addEventListener('mouseenter', () => editCatBtn.style.opacity = '1');

                editCatBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const newName = prompt(`Rename category "${catName}" in collection "${project.name}" manually?`, catName);
                    if (newName && newName !== catName) {
                        renameCategory(project.id, catName, newName);
                    }
                });

                catsEps.forEach(ep => {
                    const originalIndex = endpoints.findIndex(e => e.id === ep.id);
                    const item = document.createElement('div');
                    item.className = 'endpoint-nav-item';

                    const currentActiveId = endpoints[currentEndpointIndex]?.id;
                    if (ep.id === currentActiveId) item.classList.add('active');

                    item.innerHTML = `
                        <span class="method-badge method-${ep.method}">${ep.method}</span>
                        <span class="endpoint-label" style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${ep.name}</span>
                        ${currentUserRole === 'superadmin' ? `
                        <button class="delete-endpoint-btn icon-btn" title="Delete Endpoint" style="opacity:0; transition:opacity 0.2s; padding:2px 5px;">
                            <i class="fas fa-trash" style="font-size:0.75rem;"></i>
                        </button>
                        ` : ''}
                    `;

                    if (currentUserRole === 'superadmin') {
                        const delBtn = item.querySelector('.delete-endpoint-btn');
                        item.addEventListener('mouseenter', () => delBtn.style.opacity = '0.6');
                        item.addEventListener('mouseleave', () => delBtn.style.opacity = '0');
                        delBtn.addEventListener('mouseenter', () => delBtn.style.opacity = '1');

                        delBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            if (confirm(`Delete endpoint "${ep.name}"?`)) {
                                deleteEndpoint(ep.id);
                            }
                        });
                    }

                    item.addEventListener('click', () => {
                        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
                        loadEndpointToUI(originalIndex);
                    });
                    catDiv.appendChild(item);
                });
                pDiv.appendChild(catDiv);
            }

            endpointList.appendChild(pDiv);
        });

        // Close dropdowns when clicking elsewhere
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        });
    }

    // Top Burger Menu Logic
    if (topBurgerBtn) {
        topBurgerBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                if (m !== topBurgerDropdown) m.classList.add('hidden');
            });
            topBurgerDropdown.classList.toggle('hidden');
        });
    }

    async function deleteEndpoint(id) {
        const res = await fetch('api.php?action=delete_endpoint', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (result.status === 'success') {
            await loadEndpoints();
            if (endpoints[currentEndpointIndex]?.id == id) {
                currentEndpointIndex = -1;
                document.getElementById('current-endpoint-name').textContent = 'New Request';
            }
        } else {
            alert('Error: ' + result.message);
        }
    }

    async function renameCategory(project_id, old_name, new_name) {
        const res = await fetch('api.php?action=rename_category', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ project_id, old_name, new_name })
        });
        const result = await res.json();
        if (result.status === 'success') {
            await loadEndpoints();
        } else {
            alert('Error: ' + result.message);
        }
    }

    async function deleteProject(id) {
        const res = await fetch('api.php?action=delete_project', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (result.status === 'success') {
            await loadProjects();
            await loadEndpoints();
        } else {
            alert('Error: ' + result.message);
        }
    }

    function openEditProjectModal(project) {
        document.getElementById('project-modal-title').textContent = 'Edit Collection';
        document.getElementById('edit-project-id').value = project.id;
        document.getElementById('new-project-name').value = project.name;
        document.getElementById('new-project-desc').value = project.description || '';
        document.getElementById('confirm-project-btn').textContent = 'Update';
        projectModal.style.display = 'flex';
    }

    function loadEndpointToUI(index) {
        currentEndpointIndex = index;
        const ep = endpoints[index];
        selectedProjectId = ep.project_id;
        document.getElementById('current-endpoint-name').textContent = ep.name;
        requestMethod.value = ep.method;
        requestUrl.value = ep.url;

        const refillEditor = (containerId, items) => {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            if (items && typeof items === 'object' && Object.keys(items).length > 0) {
                for (const [k, v] of Object.entries(items)) createRow(containerId, k, v);
            } else {
                createRow(containerId);
            }
        };

        refillEditor('params-editor', ep.params);
        refillEditor('headers-editor', ep.headers);

        if (ep.body_type === 'raw') {
            document.querySelector('input[value="raw"]').checked = true;
            document.getElementById('request-body-json').value = ep.body || '';
            bodyRawContainer.classList.remove('hidden');
            bodyFormContainer.classList.add('hidden');
        } else if (ep.body_type === 'form-data') {
            document.querySelector('input[value="form-data"]').checked = true;

            let bodyData = {};
            try {
                bodyData = (typeof ep.body === 'string') ? JSON.parse(ep.body || '{}') : (ep.body || {});
            } catch (e) { bodyData = {}; }

            refillEditor('body-form-editor', bodyData);
            bodyRawContainer.classList.add('hidden');
            bodyFormContainer.classList.remove('hidden');
        } else {
            document.querySelector('input[value="none"]').checked = true;
            bodyRawContainer.classList.add('hidden');
            bodyFormContainer.classList.add('hidden');
        }
        renderSidebar();
    }

    // Modal Actions
    createProjectBtn.addEventListener('click', () => {
        document.getElementById('project-modal-title').textContent = 'Create New Collection';
        document.getElementById('edit-project-id').value = '';
        document.getElementById('new-project-name').value = '';
        document.getElementById('new-project-desc').value = '';
        document.getElementById('confirm-project-btn').textContent = 'Create';
        projectModal.style.display = 'flex';
    });

    document.getElementById('confirm-project-btn').addEventListener('click', async () => {
        const id = document.getElementById('edit-project-id').value;
        const name = document.getElementById('new-project-name').value;
        const description = document.getElementById('new-project-desc').value;
        if (!name) return alert('Name required');

        const actionType = id ? 'update_project' : 'create_project';
        const res = await fetch(`api.php?action=${actionType}`, {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ id, name, description })
        });
        const result = await res.json();
        if (result.status === 'success') {
            await loadProjects();
            await loadEndpoints();
            closeModal('create-project-modal');
        } else {
            alert('Error: ' + result.message);
        }
    });

    saveCollectionBtn.addEventListener('click', () => {
        saveModal.style.display = 'flex';
        const projectIdSelect = document.getElementById('save-project-id');
        if (currentEndpointIndex !== -1) {
            const ep = endpoints[currentEndpointIndex];
            document.getElementById('save-name').value = ep.name;
            document.getElementById('save-category').value = ep.category || '';
            projectIdSelect.value = ep.project_id;
            updateCategoryDatalist(ep.project_id);
        } else {
            document.getElementById('save-name').value = '';
            document.getElementById('save-category').value = '';
            // If we have a selected project from sidebar/state, use it
            if (selectedProjectId) projectIdSelect.value = selectedProjectId;
            updateCategoryDatalist(projectIdSelect.value);
        }
    });

    addEndpointBtn.addEventListener('click', () => {
        saveModal.style.display = 'flex';
        document.getElementById('save-name').value = '';
        document.getElementById('save-category').value = '';
        const projectIdSelect = document.getElementById('save-project-id');
        if (selectedProjectId) projectIdSelect.value = selectedProjectId;
        updateCategoryDatalist(projectIdSelect.value);
        currentEndpointIndex = -1;
    });

    // Import Collection Logic
    const importBtn = document.getElementById('import-collection-btn');
    const importModal = document.getElementById('import-collection-modal');
    const confirmImportBtn = document.getElementById('confirm-import-btn');
    const importFileInput = document.getElementById('import-file-input');

    importBtn.addEventListener('click', () => {
        importModal.style.display = 'flex';
    });

    confirmImportBtn.addEventListener('click', () => {
        const file = importFileInput.files[0];
        if (!file) return alert('Please select a JSON file');

        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const collection = JSON.parse(e.target.result);

                // Show loading state
                confirmImportBtn.disabled = true;
                confirmImportBtn.textContent = 'Importing...';

                const res = await fetch('api.php?action=import_collection', {
                    method: 'POST',
                    headers: getHeaders(),
                    body: JSON.stringify({ collection })
                });

                const result = await res.json();
                if (result.status === 'success') {
                    alert('Collection imported successfully!');
                    await loadProjects();
                    await loadEndpoints();
                    closeModal('import-collection-modal');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (err) {
                alert('Invalid JSON file: ' + err.message);
            } finally {
                confirmImportBtn.disabled = false;
                confirmImportBtn.textContent = 'Import';
                importFileInput.value = '';
            }
        };
        reader.readAsText(file);
    });

    // User Management Logic
    if (manageUsersBtn) {
        manageUsersBtn.addEventListener('click', () => {
            userModal.style.display = 'flex';
            loadUsers();
        });
    }

    async function loadUsers() {
        const res = await fetch('api.php?action=users');
        const users = await res.json();
        const tbody = document.getElementById('user-list-table-body');
        tbody.innerHTML = users.map(user => `
            <tr style="border-bottom: 1px solid var(--border-color);">
                <td style="padding: 10px;">${user.username}</td>
                <td style="padding: 10px;"><span class="urole" style="font-size: 0.7rem;">${user.role.toUpperCase()}</span></td>
                <td style="padding: 10px; display: flex; gap: 5px;">
                    <button class="icon-btn edit-user-btn" data-id="${user.id}" data-username="${user.username}" title="Edit Password">
                        <i class="fas fa-key" style="color: var(--accent-primary);"></i>
                    </button>
                    ${user.username !== 'admin' ? `
                        <button class="icon-btn delete-user-btn" data-id="${user.id}" title="Delete User">
                            <i class="fas fa-user-minus" style="color: var(--error);"></i>
                        </button>
                    ` : '<span style="color:var(--text-dim); font-size:0.8rem; margin-left: 5px;">Protected</span>'}
                </td>
            </tr>
        `).join('');

        // Edit User Password Event
        tbody.querySelectorAll('.edit-user-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const username = btn.getAttribute('data-username');
                document.getElementById('edit-user-id').value = id;
                document.getElementById('edit-user-username').value = username;
                document.getElementById('edit-user-password').value = '';
                editUserModal.style.display = 'flex';
            });
        });

        // Delete User Event
        tbody.querySelectorAll('.delete-user-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this user?')) {
                    const res = await fetch('api.php?action=delete_user', {
                        method: 'POST',
                        headers: getHeaders(),
                        body: JSON.stringify({ id })
                    });
                    const result = await res.json();
                    if (result.status === 'success') {
                        loadUsers();
                    } else {
                        alert('Error: ' + result.message);
                    }
                }
            });
        });
    }

    document.getElementById('confirm-add-user-btn')?.addEventListener('click', async () => {
        const username = document.getElementById('new-user-username').value;
        const password = document.getElementById('new-user-password').value;
        const role = document.getElementById('new-user-role').value;

        if (!username || !password) return alert('Username & Password required');

        const res = await fetch('api.php?action=create_user', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ username, password, role })
        });
        const result = await res.json();

        if (result.status === 'success') {
            document.getElementById('new-user-username').value = '';
            document.getElementById('new-user-password').value = '';
            loadUsers();
        } else {
            alert('Error: ' + result.message);
        }
    });

    document.getElementById('confirm-save-btn').addEventListener('click', async () => {
        const name = document.getElementById('save-name').value;
        const project_id = document.getElementById('save-project-id').value;
        const category = document.getElementById('save-category').value || 'Default';

        if (!name || !project_id) return alert('Name & Collection required');

        const payload = {
            id: currentEndpointIndex !== -1 ? endpoints[currentEndpointIndex].id : null,
            project_id,
            name,
            category,
            method: requestMethod.value,
            url: requestUrl.value,
            params: getKVData('params-editor'),
            headers: getKVData('headers-editor'),
            bodyType: document.querySelector('input[name="body-type"]:checked').value,
            body: document.querySelector('input[name="body-type"]:checked').value === 'raw'
                ? document.getElementById('request-body-json').value
                : getKVData('body-form-editor')
        };

        const res = await fetch('api.php?action=save_endpoint', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result.status === 'success') {
            await loadEndpoints(); // Refresh data

            // Re-select the saved endpoint by ID
            if (result.id) {
                const newIndex = endpoints.findIndex(e => e.id == result.id);
                if (newIndex !== -1) currentEndpointIndex = newIndex;
            }

            renderSidebar();
            closeModal('save-endpoint-modal');
        } else {
            alert('Error: ' + result.message);
        }
    });

    function getKVData(containerId) {
        const data = {};
        document.querySelectorAll(`#${containerId} .key-value-row`).forEach(row => {
            const k = row.querySelector('.kv-key').value;
            const v = row.querySelector('.kv-value').value;
            if (k) data[k] = v;
        });
        return data;
    }

    sendBtn.addEventListener('click', async () => {
        const method = requestMethod.value;
        const url = requestUrl.value;
        if (!url) return alert('URL required');

        sendBtn.disabled = true;
        responseOutput.textContent = 'Sending...';
        responseMeta.classList.add('hidden');

        try {
            const headers = getKVData('headers-editor');
            const bodyType = document.querySelector('input[name="body-type"]:checked').value;
            const body = bodyType === 'raw'
                ? document.getElementById('request-body-json').value
                : (bodyType === 'form-data' ? getKVData('body-form-editor') : '');

            const res = await fetch('api.php?action=send_request', {
                method: 'POST',
                headers: getHeaders(),
                body: JSON.stringify({ url, method, headers, body, env_id: envSelector.value })
            });

            const result = await res.json();
            if (result.status === 'success') {
                try {
                    // Try to parse as JSON for pretty print
                    const parsed = JSON.parse(result.data);
                    const jsonStr = JSON.stringify(parsed, null, 4);
                    responseOutput.innerHTML = syntaxHighlight(jsonStr);
                } catch (e) {
                    // Not JSON, just show as is with line numbers if possible
                    responseOutput.innerHTML = addLineNumbers(result.data || '(Empty response)');
                }

                responseStatus.textContent = result.http_code;
                responseTime.textContent = `${result.time} ms`;
                responseSize.textContent = `${(result.size / 1024).toFixed(2)} KB`;
                responseMeta.classList.remove('hidden');
            } else {
                responseOutput.textContent = 'Error: ' + result.message;
            }
        } catch (e) {
            responseOutput.textContent = 'Error: ' + e.message;
        } finally {
            sendBtn.disabled = false;
        }
    });

    // Profile Logic
    if (myProfileBtn) {
        myProfileBtn.addEventListener('click', () => {
            document.getElementById('current-user-old-password').value = '';
            document.getElementById('current-user-new-password').value = '';
            profileModal.style.display = 'flex';
        });
    }

    // Logs Logic
    if (viewLogsBtn) {
        viewLogsBtn.addEventListener('click', () => {
            logsModal.style.display = 'flex';
            loadLogs();
        });
    }

    async function loadLogs() {
        const res = await fetch('api.php?action=audit_logs');
        const logs = await res.json();
        const tbody = document.getElementById('logs-table-body');

        if (logs.status === 'error') {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:20px; color:var(--error);">${logs.message}</td></tr>`;
            return;
        }

        tbody.innerHTML = logs.map(log => {
            const date = new Date(log.created_at).toLocaleString();
            return `
                <tr style="border-bottom: 1px solid var(--border-color); vertical-align: top;">
                    <td style="padding: 10px; color: var(--text-dim); font-size: 0.75rem;">${date}</td>
                    <td style="padding: 10px; font-weight: 600;">${log.performer_name || 'System'}</td>
                    <td style="padding: 10px;"><span class="urole" style="background: rgba(129, 140, 248, 0.1); color: #818cf8; border-color: rgba(129, 140, 248, 0.2);">${log.action}</span></td>
                    <td style="padding: 10px; color: var(--text-main); line-height: 1.4;">${log.details}</td>
                </tr>
            `;
        }).join('');
    }

    document.getElementById('confirm-profile-update-btn')?.addEventListener('click', async () => {
        const old_password = document.getElementById('current-user-old-password').value;
        const new_password = document.getElementById('current-user-new-password').value;

        if (!old_password || !new_password) return alert('Current and new password required');

        const res = await fetch('api.php?action=update_profile', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ old_password, new_password })
        });
        const result = await res.json();

        if (result.status === 'success') {
            alert('Profile updated successfully');
            closeModal('profile-modal');
        } else {
            alert('Error: ' + result.message);
        }
    });

    document.getElementById('confirm-edit-user-btn')?.addEventListener('click', async () => {
        const id = document.getElementById('edit-user-id').value;
        const password = document.getElementById('edit-user-password').value;

        if (!password) return alert('New password required');

        const res = await fetch('api.php?action=update_user', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ id, password })
        });
        const result = await res.json();

        if (result.status === 'success') {
            alert('Password updated successfully');
            closeModal('edit-user-modal');
        } else {
            alert('Error: ' + result.message);
        }
    });

    function syntaxHighlight(json) {
        if (!json) return "";
        json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const highlighted = json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
            var cls = 'json-number';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'json-key';
                } else {
                    cls = 'json-string';
                }
            } else if (/true|false/.test(match)) {
                cls = 'json-boolean';
            } else if (/null/.test(match)) {
                cls = 'json-null';
            }
            return '<span class="' + cls + '">' + match + '</span>';
        });

        return addLineNumbers(highlighted);
    }

    function addLineNumbers(content) {
        const lines = content.split('\n');
        return lines.map((line, i) =>
            `<div class="code-line"><span class="line-number">${i + 1}</span><span class="line-content">${line || ' '}</span></div>`
        ).join('');
    }

    // Environment Logic
    if (manageEnvsBtn) {
        manageEnvsBtn.addEventListener('click', () => {
            envsModal.style.display = 'flex';
            envEditSection.classList.add('hidden');
            document.getElementById('env-modal-main-actions').classList.remove('hidden');
            loadEnvironments();
        });
    }

    async function loadEnvironments() {
        const res = await fetch('api.php?action=environments');
        environments = await res.json();
        renderEnvSelector();
        renderEnvList();
    }

    function renderEnvSelector() {
        const currentVal = envSelector.value;
        envSelector.innerHTML = '<option value="">No Environment</option>' +
            environments.map(env => `<option value="${env.id}" ${env.id == currentVal ? 'selected' : ''}>${env.name}</option>`).join('');
    }

    function renderEnvList() {
        envItemsList.innerHTML = environments.map(env => `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid var(--border-color);">
                <span style="font-weight: 600;">${env.name}</span>
                <div style="display: flex; gap: 10px;">
                    <button class="icon-btn" onclick="openEditEnv(${env.id})" title="Edit"><i class="fas fa-edit"></i></button>
                    ${currentUserRole === 'superadmin' ? `<button class="icon-btn" onclick="deleteEnv(${env.id})" title="Delete"><i class="fas fa-trash" style="color:var(--error);"></i></button>` : ''}
                </div>
            </div>
        `).join('') || '<div style="padding: 20px; text-align: center; color: var(--text-dim);">No environments found</div>';
    }

    window.openEditEnv = (id) => {
        const env = id ? environments.find(e => e.id == id) : { id: '', name: '', variables: {} };
        document.getElementById('edit-env-id').value = env.id;
        document.getElementById('edit-env-name').value = env.name;

        const container = document.getElementById('env-vars-editor');
        container.innerHTML = '';
        if (env.variables && Object.keys(env.variables).length > 0) {
            for (const [k, v] of Object.entries(env.variables)) createRow('env-vars-editor', k, v);
        } else {
            createRow('env-vars-editor');
        }

        envEditSection.classList.remove('hidden');
        document.getElementById('env-modal-main-actions').classList.add('hidden');
    };

    document.getElementById('add-env-btn').addEventListener('click', () => openEditEnv(null));
    document.getElementById('cancel-env-edit-btn').addEventListener('click', () => {
        envEditSection.classList.add('hidden');
        document.getElementById('env-modal-main-actions').classList.remove('hidden');
    });

    document.getElementById('save-env-btn').addEventListener('click', async () => {
        const id = document.getElementById('edit-env-id').value;
        const name = document.getElementById('edit-env-name').value;
        const variables = getKVData('env-vars-editor');

        if (!name) return alert('Environment name required');

        const action = id ? 'update_environment' : 'create_environment';
        const res = await fetch(`api.php?action=${action}`, {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ id, name, variables })
        });
        const result = await res.json();
        if (result.status === 'success') {
            loadEnvironments();
            envEditSection.classList.add('hidden');
            document.getElementById('env-modal-main-actions').classList.remove('hidden');
        } else {
            alert('Error: ' + result.message);
        }
    });

    window.deleteEnv = async (id) => {
        if (!confirm('Are you sure you want to delete this environment?')) return;
        const res = await fetch('api.php?action=delete_environment', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (result.status === 'success') {
            loadEnvironments();
        } else {
            alert('Error: ' + result.message);
        }
    };

    // Initialize
    loadProjects().then(() => {
        loadEndpoints();
        loadEnvironments();
    });
});

@php
    $componentId = 'software-billing-' . str_replace('.', '', uniqid('', true));
@endphp

<div id="{{ $componentId }}" class="software-billing-package">
    <button type="button" class="software-billing-open">
        {{ $label ?? 'Billing Configuration' }}
    </button>

    <div class="software-billing-modal" style="display:none">
        <div class="software-billing-panel" role="dialog" aria-modal="true">
            <div class="software-billing-header">
                <h3 class="software-billing-title">Bill Information</h3>
                <button type="button" class="software-billing-close" aria-label="Close">X</button>
            </div>

            <p class="software-billing-message"></p>
            <p class="software-billing-loading">Loading billing data...</p>

            <section class="software-billing-details">
                <h4>Bill Information</h4>
                <div class="software-billing-detail-list"></div>
                <div class="software-billing-payment">
                    <strong>এই বিলটি কি পরিশোধ হয়েছে?</strong>
                    <div class="software-billing-payment-actions">
                        <button type="button" data-status="2">হ্যাঁ</button>
                        <button type="button" data-status="0">না</button>
                    </div>
                </div>
                <button type="button" class="software-billing-edit">
                    Edit Configuration
                </button>
            </section>

            <section class="software-billing-configuration" style="display:none">
                <h4>Billing API Configuration</h4>
                <form class="software-billing-form">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                    <label>
                        API URL
                        <input type="url" name="api_url" maxlength="500" required>
                    </label>

                    <label>
                        API Token
                        <input type="password" name="api_token"
                               autocomplete="new-password"
                               placeholder="Leave blank to keep the existing token">
                    </label>

                    <label>
                        Fetch Interval (minutes)
                        <input type="number" name="fetch_interval" min="1" max="10080" required>
                    </label>

                    <label>
                        Status
                        <select name="status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </label>

                    <button type="submit">Save Configuration</button>
                </form>
            </section>
        </div>
    </div>
</div>

<style>
.software-billing-package { display:inline-block; }
.software-billing-open {
    padding:10px 16px; border:0; border-radius:5px;
    background:#2563eb; color:#fff; cursor:pointer;
}
.software-billing-open.has-bill,
.software-billing-open.has-bill:hover { background:#dc2626; }
.software-billing-open.has-bill:hover { background:#b91c1c; }
.software-billing-modal {
    position:fixed; inset:0; z-index:99999; padding:20px;
    box-sizing:border-box; background:rgba(0,0,0,.5);
    align-items:center; justify-content:center;
}
.software-billing-panel {
    width:100%; max-width:1000px; max-height:90vh; overflow:auto;
    box-sizing:border-box; padding:24px; border-radius:10px;
    background:#fff; color:#222; box-shadow:0 15px 40px rgba(0,0,0,.2);
}
.software-billing-panel.has-latest-bill { max-width:900px; }
.software-billing-header { display:flex; align-items:center; justify-content:space-between; }
.software-billing-header h3 { margin:0; }
.software-billing-close { border:0; background:transparent; cursor:pointer; font-size:18px; }
.software-billing-configuration { max-width:500px; }
.software-billing-form label { display:block; margin-top:12px; }
.software-billing-form input,
.software-billing-form select {
    display:block; width:100%; box-sizing:border-box; margin-top:5px;
    padding:9px; border:1px solid #ccc; border-radius:5px;
}
.software-billing-form button {
    margin-top:16px; padding:9px 14px; border:0; border-radius:5px;
    background:#2563eb; color:#fff; cursor:pointer;
}
.software-billing-edit {
    margin-top:16px; padding:9px 14px; border:1px solid #ccc;
    border-radius:5px; background:#fff; cursor:pointer;
}
.software-billing-detail-list {
    display:grid; grid-template-columns:minmax(140px, 1fr) 2fr; gap:8px 16px;
}
.software-billing-detail-list dt { font-weight:600; }
.software-billing-detail-list dd { margin:0; overflow-wrap:anywhere; }
.software-billing-detail-list dl { display:contents; }
.software-billing-detail-list div {
    display:contents;
}
.software-billing-message { color:#b91c1c; }
.software-billing-loading { color:#555; }
.software-billing-payment {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    margin-top:16px; padding:14px; border:1px solid #dbeafe;
    border-radius:10px; background:#f8fafc;
}
.software-billing-payment-actions { display:flex; gap:8px; }
.software-billing-payment-actions button {
    padding:8px 14px; border:1px solid #2563eb; border-radius:6px;
    background:#2563eb; color:#fff; cursor:pointer;
}
.software-billing-payment-actions button:last-child { background:#fff; color:#dc2626; border-color:#dc2626; }
</style>

<script>
(function () {
    const root = document.getElementById(@json($componentId));
    if (!root || root.dataset.initialized === '1') return;
    root.dataset.initialized = '1';

    const modal = root.querySelector('.software-billing-modal');
    const form = root.querySelector('.software-billing-form');
    const message = root.querySelector('.software-billing-message');
    const loading = root.querySelector('.software-billing-loading');
    const button = root.querySelector('.software-billing-open');
    const title = root.querySelector('.software-billing-title');
    const details = root.querySelector('.software-billing-details');
    const detailList = root.querySelector('.software-billing-detail-list');
    const configuration = root.querySelector('.software-billing-configuration');
    const panel = root.querySelector('.software-billing-panel');
    const payment = root.querySelector('.software-billing-payment');
    let latestBill = null;
    let dataLoaded = false;
    let loadingRequest = null;

    function closeModal() {
        modal.style.display = 'none';
    }

    function showBillInformation() {
        title.textContent = 'Bill Information';
        details.style.display = 'block';
        configuration.style.display = 'none';
        payment.style.display = 'flex';
        panel.classList.add('has-latest-bill');
    }

    function showConfiguration() {
        title.textContent = 'Software Billing Configuration';
        details.style.display = 'none';
        configuration.style.display = 'block';
        payment.style.display = 'none';
        panel.classList.remove('has-latest-bill');
    }

    button.addEventListener('click', async function () {
        modal.style.display = 'flex';
        if (!dataLoaded) {
            await loadData();
        }
        if (latestBill) {
            showBillInformation();
        } else {
            showConfiguration();
        }
    });
    root.querySelector('.software-billing-close').addEventListener('click', closeModal);
    root.querySelector('.software-billing-edit').addEventListener('click', showConfiguration);
    root.querySelectorAll('.software-billing-payment-actions button').forEach(function (statusButton) {
        statusButton.addEventListener('click', async function () {
            if (!latestBill) return;
            const status = Number(statusButton.dataset.status);
            statusButton.disabled = true;
            try {
                const response = await fetch(@json(route('software-billing.latest_bill.status')), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': form.elements._token.value
                    },
                    body: JSON.stringify({ status: status })
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Unable to update bill status.');
                latestBill.status = Number(data.status);
                renderBill(latestBill);
                message.style.color = '#15803d';
                message.textContent = data.message;
            } catch (error) {
                message.style.color = '#b91c1c';
                message.textContent = error.message;
            } finally {
                statusButton.disabled = false;
            }
        });
    });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    function renderBill(bill) {
        detailList.innerHTML = '';
        if (!bill) {
            const empty = document.createElement('p');
            empty.textContent = 'No billing data found.';
            detailList.appendChild(empty);
            return;
        }

        const list = document.createElement('dl');
        Object.keys(bill).forEach(function (key) {
            const row = document.createElement('div');
            const label = document.createElement('dt');
            const value = document.createElement('dd');
            label.textContent = key;
            if (key === 'status') {
                value.textContent = bill[key] === 2 || bill[key] === '2'
                    ? 'Paid'
                    : (bill[key] === 1 || bill[key] === '1' ? 'Partial' : 'Unpaid');
            } else {
                value.textContent = bill[key] === null || bill[key] === undefined
                    ? ''
                    : (typeof bill[key] === 'object' ? JSON.stringify(bill[key]) : String(bill[key]));
            }
            row.append(label, value);
            list.appendChild(row);
        });
        detailList.appendChild(list);
    }

    function loadData() {
        if (dataLoaded) return Promise.resolve();
        if (loadingRequest) return loadingRequest;

        loading.style.display = 'block';
        message.textContent = '';

        loadingRequest = (async function () {
            try {
                const response = await fetch(@json(route('software-billing.bill_configuration')), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin'
                });
                const data = await response.json();

                if (!response.ok) throw new Error(data.message || 'Unable to load billing data.');

                const config = data.configuration || {};
                form.elements.api_url.value = config.api_url || '';
                form.elements.fetch_interval.value = config.fetch_interval || 60;
                form.elements.status.value = String(config.status ?? 1);
                form.elements.api_token.value = '';
                latestBill = data.latest_bill && typeof data.latest_bill === 'object'
                    ? data.latest_bill
                    : null;

                if (
                    latestBill
                    && latestBill.total_bill_amount !== null
                    && latestBill.total_bill_amount !== undefined
                ) {
                    button.textContent = String(latestBill.total_bill_amount);
                    button.classList.add('has-bill');
                }
                renderBill(latestBill);
                dataLoaded = true;

                if (data.fetch_error) {
                    message.style.color = '#b91c1c';
                    message.textContent = data.fetch_error;
                } else {
                    message.textContent = '';
                }
            } catch (error) {
                message.textContent = error.message;
            } finally {
                loading.style.display = 'none';
                loadingRequest = null;
            }
        })();

        return loadingRequest;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        message.textContent = '';

        const payload = {
            api_url: form.elements.api_url.value,
            fetch_interval: Number(form.elements.fetch_interval.value),
            status: Number(form.elements.status.value),
        };

        if (form.elements.api_token.value) {
            payload.api_token = form.elements.api_token.value;
        }

        try {
            const response = await fetch(@json(route('software-billing.bill_configuration.store')), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.elements._token.value
                },
                body: JSON.stringify(payload)
            });
            const data = await response.json();

            if (!response.ok) {
                const validationErrors = data.errors
                    ? Object.values(data.errors).flat()
                    : [];
                throw new Error(validationErrors.join(' ') || data.message ||
                    'Unable to save configuration.');
            }

            form.elements.api_token.value = '';
            message.style.color = '#15803d';
            message.textContent = data.message || 'Configuration saved successfully.';
            dataLoaded = false;
            await loadData();
            if (latestBill) {
                showBillInformation();
            }
        } catch (error) {
            message.style.color = '#b91c1c';
            message.textContent = error.message;
        }
    });

    loadData();
})();
</script>
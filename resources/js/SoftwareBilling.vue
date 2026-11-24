<template>
  <div class="software-billing">
    <button type="button" class="billing-button" :class="{ 'billing-button--bill': latestBill }" @click="openModal">
      <template v-if="latestBill">
        <span class="fw-bold">Due Bill: </span>
        <span class="fw-bold">৳ {{ latestBill.total_bill_amount }}</span>
      </template>
      <template v-else>{{ label }}</template>
    </button>

    <div v-if="showModal" class="billing-overlay"  @click.self="closeModal" >
      <div class="billing-modal" :class="{ 'billing-modal--bill': latestBill && !showConfiguration }" role="dialog" aria-modal="true" aria-labelledby="billing-title" >
        <div class="billing-header">
          <h3 id="billing-title">
            {{ latestBill && !showConfiguration ? 'Bill Information' : 'Software Billing Configuration' }}
          </h3>

          <button type="button"  class="close-button" aria-label="Close"  @click="closeModal" > ✕ </button>
        </div>

        <p v-if="success" class="success"> {{ success }} </p>

        <p v-if="error" class="error"> {{ error }} </p>

        <div v-if="loadingConfig" class="loading-message"> Loading configuration... </div>

        <div v-else-if="latestBill && !showConfiguration" class="invoice-wrapper card border-0 shadow-sm rounded-3">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 pb-3 mb-4 border-bottom">
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary text-uppercase px-2 py-1">
                  {{ latestBill.bill_type || 'Monthly' }} Invoice
                </span>
                  <span class="text-muted small fw-medium">
                #{{ latestBill.bill_id || latestBill.id }}
              </span>
                </div>
                <h5 class="fw-bold text-dark mb-0">Bill Invoice</h5>
                <small class="text-muted">Billing Month: <strong class="text-dark">{{ latestBill.bill_month }}</strong></small>
              </div>

              <div class="text-md-end">
                <div class="mb-1">
          <span class="fw-bold rounded-pill px-3 py-2" :class="statusBadgeClass">
            {{ statusLabel }}
          </span>
                </div>
                <div class="small text-muted">
                  Due Date: <strong class="text-danger">{{ formatShortDate(latestBill.due_date) }}</strong>
                </div>
              </div>
            </div>

            <!-- ২. Dates & Billing Period Meta -->
            <div class="row g-2 bg-light rounded-3 p-3 mb-4 small">
              <div class="col-sm-4">
                <div class="text-muted">Generate Date</div>
                <div class="fw-semibold text-dark">{{ formatShortDate(latestBill.generate_date) }}</div>
              </div>
              <div class="col-sm-4">
                <div class="text-muted">Billing Start Date</div>
                <div class="fw-semibold text-dark">{{ formatShortDate(latestBill.billing_start_date) }}</div>
              </div>
              <div class="col-sm-4">
                <div class="text-muted">Billing End Date</div>
                <div class="fw-semibold text-dark">{{ formatShortDate(latestBill.billing_end_date) }}</div>
              </div>
            </div>

            <!-- ৩. Invoice Amount Breakdown Table -->
            <div class="table-responsive mb-4">
              <table class="table table-sm table-borderless align-middle mb-0 invoice-table">
                <thead class="border-bottom text-muted small text-uppercase">
                <tr>
                  <th class="py-2 ps-0">Description</th>
                  <th class="py-2 pe-0 text-end">Amount (BDT)</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                <tr class="border-bottom">
                  <td class="py-2 ps-0 text-secondary">Amount</td>
                  <td class="py-2 pe-0 text-end fw-medium text-dark">৳ {{ formatMoney(latestBill.amount) }}</td>
                </tr>
                <tr class="border-bottom">
                  <td class="py-2 ps-0 text-secondary">Paid Amount</td>
                  <td class="py-2 pe-0 text-end fw-medium text-success">- ৳ {{ formatMoney(latestBill.paid_amount) }}</td>
                </tr>
                <tr class="border-bottom">
                  <td class="py-2 ps-0 text-secondary">Due Amount</td>
                  <td class="py-2 pe-0 text-end fw-semibold text-warning-emphasis">৳ {{ formatMoney(latestBill.due_amount) }}</td>
                </tr>
                <tr class="border-bottom">
                  <td class="py-2 ps-0 text-secondary">Bill Amount</td>
                  <td class="py-2 pe-0 text-end fw-medium text-dark">৳ {{ formatMoney(latestBill.bill_amount) }}</td>
                </tr>
                <tr class="border-bottom">
                  <td class="py-2 ps-0 text-secondary">Pre Due Bill Amount</td>
                  <td class="py-2 pe-0 text-end fw-medium text-dark">৳ {{ formatMoney(latestBill.pre_due_bill_amount) }}</td>
                </tr>
                <tr class="border-bottom bg-light-subtle">
                  <td class="py-2 ps-2 fw-semibold text-dark">Total Due Bill Amount</td>
                  <td class="py-2 pe-2 text-end fw-bold text-dark">৳ {{ formatMoney(latestBill.total_due_bill_amount) }}</td>
                </tr>
                </tbody>
                <tfoot>
                <tr class="table-primary">
                  <th class="py-3 ps-3 rounded-start fs-6 text-primary-emphasis">Total Bill Amount</th>
                  <th class="py-3 pe-3 rounded-end text-end fs-5 fw-bold text-primary-emphasis">
                    ৳ {{ formatMoney(latestBill.total_bill_amount) }}
                  </th>
                </tr>
                </tfoot>
              </table>
            </div>

            <!-- ৪. Remarks / Notes -->
            <div v-if="latestBill.remarks" class="alert alert-light border rounded-3 py-2 px-3 mb-4 small">
              <span class="fw-semibold text-dark">Remarks:</span>
              <span class="text-black">{{ latestBill.remarks }}</span>
            </div>

            <div class="payment-confirmation mb-3" v-if="latestBill.total_bill_amount > 0">
              <div>
                <strong>Has this bill been paid?</strong>
                <small v-if="statusLabel !== 'Paid'" class="d-block text-muted">Select Yes or No</small>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm px-3" :disabled="paymentUpdating" @click="markBillStatus(1)"> Yes </button>
                <button type="button" class="btn btn-outline-danger btn-sm px-3" :disabled="paymentUpdating" @click="markBillStatus(0)"> No </button>
              </div>
            </div>

            <!-- ৫. Footer Actions -->
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
              <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-medium" @click="showConfiguration = true" >
                Edit Configuration
              </button>
              <button type="button" class="btn btn-secondary btn-sm px-3" @click="closeModal" > Close </button>
            </div>

          </div>
        </div>

        <form v-else @submit.prevent="saveConfiguration">
          <label for="billing-api-url">API URL</label>
          <input id="billing-api-url"  v-model.trim="form.api_url" type="url"  placeholder="https://example.com/api" required />

          <label for="billing-api-token">API Token</label>
          <input id="billing-api-token" v-model="form.api_token" type="password" autocomplete="new-password" :placeholder="tokenConfigured
            ? 'Token already saved; leave blank to keep it' : 'Enter API token' " />

          <small v-if="tokenConfigured" class="help-text">
            A token is already configured. Leave this field blank
            to retain the existing token.
          </small>

          <label for="billing-fetch-interval"> Fetch Interval (minutes) </label>
          <input id="billing-fetch-interval" v-model.number="form.fetch_interval" type="number"  min="1" max="10080" required />

          <label for="billing-status">Status</label>
          <select id="billing-status" v-model.number="form.status" >
            <option :value="1">Active</option>
            <option :value="0">Inactive</option>
          </select>

          <p v-if="lastFetchAt" class="last-fetch">
            Last successful fetch: {{ lastFetchAt }}
          </p>
          <p v-if="fetchError" class="error"> {{ fetchError }} </p>

          <div v-if="bills.length" class="billing-table">
            <h4>Billing Data</h4>
            <div class="billing-table-scroll">
              <table>
                <thead>
                <tr>
                  <th v-for="key in billColumns" :key="key">{{ key }}</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(bill, index) in bills" :key="index">
                  <td v-for="key in billColumns" :key="key">
                    {{ formatBillValue(bill[key]) }}
                  </td>
                </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="billing-actions">
            <button type="button" class="cancel-button" @click="closeModal"> Cancel</button>
            <button type="submit" class="save-button" :disabled="saving"> {{
                saving ? 'Saving...' : 'Save Configuration'
              }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SoftwareBilling',
  props: {
    label: {
      type: String,
      default: 'Billing Configuration',
    },
    csrfToken: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      showModal: false,
      loadingConfig: false,
      saving: false,
      success: '',
      error: '',
      tokenConfigured: false,
      lastFetchAt: null,
      fetchError: '',
      latestBill: null,
      showConfiguration: false,
      dataLoaded: false,
      bills: [],
      paymentUpdating: false,

      form: {
        api_url: '',
        api_token: '',
        fetch_interval: 60,
        status: 1,
      },
    };
  },

  mounted() {
    this.loadData();
  },

  methods: {
    formatShortDate(dateStr) {
      if (!dateStr) return '-';
      const d = new Date(dateStr);
      if (isNaN(d)) return dateStr;
      return d.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      });
    },
    formatMoney(val) {
      const num = Number(val);
      if (isNaN(num)) return val ?? '0.00';
      return num.toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    getCsrfToken() {
      if (this.csrfToken) {
        return this.csrfToken;
      }
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    },


    async apiRequest(method, url, data = null) {
      const csrfToken = this.getCsrfToken();
      const requestMethod = method.toUpperCase();

      if (requestMethod !== 'GET' && !csrfToken) {
        throw new Error('CSRF token not found. Please reload the page and try again.');
      }

      try {
        const response = await fetch(url, {
          method: requestMethod,
          credentials: 'same-origin',
          headers: {
            Accept: 'application/json',
            ...(data && {'Content-Type': 'application/json'}),
            ...(csrfToken && {'X-CSRF-TOKEN': csrfToken}),
          },
          body: data ? JSON.stringify(data) : undefined,
        });
        const responseData = await response.json();
        if (response.status === 419) {
          throw new Error('CSRF token mismatch or session expired. Please reload the page and try again.');
        }
        if (response.status === 401) {
          throw new Error('Your session has expired. Please log in again.');
        }
        if (response.status === 403) {
          throw new Error(responseData.message || 'You are not authorized to perform this action.');
        }
        if (response.status === 422) {
          const errors = Object.values(responseData.errors || {}).flat();
          throw new Error(errors.length ? errors.join(' ') : responseData.message || 'Input validation failed.');
        }
        if (!response.ok) {
          throw new Error(responseData.message || `Request failed with status ${response.status}.`);
        }
        return responseData;
      } catch (error) {
        if (error instanceof TypeError) {
          throw new Error('Unable to connect to the server. Please check your connection.');
        }
        throw error;
      }
    },


    async loadData() {
      if (this.loadingConfig) {
        return;
      }

      this.loadingConfig = true;
      this.error = '';

      try {
        const data = await this.apiRequest('get', '/software-billing/bill_configuration');
        const config = data.configuration || data;
        this.form.api_url = config.api_url || '';
        this.form.fetch_interval = config.fetch_interval ?? 60;
        this.form.status = config.status ?? 1;
        this.tokenConfigured = Boolean(config.token_configured);
        this.lastFetchAt = config.last_fetch_at || null;
        this.fetchError = data.fetch_error || '';
        this.bills = Array.isArray(data.bills) ? data.bills : [];
        this.latestBill = data.latest_bill && typeof data.latest_bill === 'object' ? data.latest_bill : null;
        this.form.api_token = '';
        this.dataLoaded = true;
        if (this.showModal) {
          this.showConfiguration = !this.latestBill;
        }
      } catch (error) {
        this.error = error.message;
      } finally {
        this.loadingConfig = false;
      }
    },

    async openModal() {
      this.showModal = true;
      this.success = '';
      this.showConfiguration = !this.latestBill;
      if (!this.dataLoaded && !this.loadingConfig) {
        await this.loadData();
      }
    },

    closeModal() {
      if (this.saving) {
        return;
      }

      this.showModal = false;
      this.error = '';
      this.success = '';
    },

    async saveConfiguration() {
      if (this.saving) {
        return;
      }
      this.saving = true;
      this.error = '';
      this.success = '';
      try {
        const payload = {
          api_url: this.form.api_url,
          api_token: this.form.api_token,
          fetch_interval: this.form.fetch_interval,
          status: this.form.status,
        };

        const data = await this.apiRequest('post', '/software-billing/bill_configuration', payload);

        this.success = data.message || 'Configuration saved successfully.';
        if (typeof data.token_configured === 'boolean') {
          this.tokenConfigured = data.token_configured;
        } else if (this.form.api_token) {
          this.tokenConfigured = true;
        }
        this.form.api_token = '';
      } catch (error) {
        this.error = error.message;
      } finally {
        this.saving = false;
      }
    },

    async markBillStatus(status) {
      const _this =this;
      if (!_this.latestBill || _this.paymentUpdating) {
        return;
      }
      _this.paymentUpdating = true;
      _this.error = '';
      _this.success = '';
      try {
        if (status===1){
           await _this.loadData();
           if (_this.latestBill.total_bill_amount > 0){
             _this.error ='আপনি মিথ্যা কথা বলছেন। আপনার বিল এখনো পরিশোধ হয়নি'
           }
        }else{
          _this.error ='বিল টি তারাতারি পরিশোধ করুন.'
        }
      } catch (error) {
        _this.error = error.message;
      } finally {
        this.paymentUpdating = false;
      }
    },

    formatBillValue(value) {
      if (value === null || value === undefined) return '';
      if (typeof value === 'object') return JSON.stringify(value);
      return String(value);
    },
  },
  computed: {
    statusLabel() {
      const status = Number(this.latestBill?.status);
      if (status === 2) return 'Paid';
      if (status === 1) return 'Partial';
      return 'Unpaid';
    },
    statusBadgeClass() {
      const status = Number(this.latestBill?.status);
      if (status === 2) return 'bg-success text-black';
      if (status === 1) return 'bg-warning text-white';
      return 'bg-danger text-black';
    },
    billColumns() {
      const columns = this.latestBill ? Object.keys(this.latestBill) : [];
      if (columns.length) {
        return columns;
      }

      this.bills.forEach((bill) => {
        Object.keys(bill || {}).forEach((key) => {
          if (!columns.includes(key)) columns.push(key);
        });
      });
      return columns;
    },
  },
};
</script>

<style scoped>
.billing-button--bill {
  animation: pulse-border 1.5s infinite;
}

@keyframes pulse-border {
  0% {
    box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
  }
  70% {
    box-shadow: 0 0 0 8px rgba(220, 53, 69, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
  }
}
.software-billing {
  display: inline-block;
}

.billing-button,
.save-button {
  background: #2563eb;
  color: #fff;
  border: 0;
  border-radius: 5px;
  padding: 10px 16px;
  cursor: pointer;
}

.billing-button:hover,
.save-button:hover:not(:disabled) {
  background: #1d4ed8;
}

.billing-button--bill,
.billing-button--bill:hover {
  background: #dc2626;
}

.billing-button--bill:hover {
  background: #b91c1c;
}

.billing-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  box-sizing: border-box;
}

.billing-modal {
  background: #fff;
  color: #222;
  width: 100%;
  max-width: 560px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 24px;
  border-radius: 10px;
  box-sizing: border-box;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
}

.billing-modal--bill {
  max-width: 900px;
}

.payment-confirmation {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  border: 1px solid #dbeafe;
  border-radius: 10px;
  background: #f8fafc;
}

.billing-header,
.billing-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.billing-header h3 {
  margin: 0;
  font-size: 20px;
}

.close-button {
  border: 0;
  background: transparent;
  color: #444;
  font-size: 20px;
  cursor: pointer;
}

.billing-modal label {
  display: block;
  margin-top: 14px;
  margin-bottom: 5px;
  font-weight: 600;
}

.billing-modal input,
.billing-modal select {
  display: block;
  box-sizing: border-box;
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
  background: #fff;
  color: #222;
}

.billing-modal input:focus,
.billing-modal select:focus {
  outline: 2px solid #93c5fd;
  border-color: #2563eb;
}

.help-text {
  display: block;
  margin-top: 6px;
  color: #666;
  font-size: 12px;
}

.billing-actions {
  justify-content: flex-end;
  margin-top: 20px;
  flex-wrap: wrap;
}

.cancel-button {
  padding: 10px 16px;
  border: 1px solid #ccc;
  background: #fff;
  border-radius: 5px;
  cursor: pointer;
}

.save-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.success {
  color: #15803d;
  background: #f0fdf4;
  padding: 10px;
  border-radius: 5px;
}

.error {
  color: #b91c1c;
  background: #fef2f2;
  padding: 10px;
  border-radius: 5px;
}

.loading-message,
.last-fetch {
  color: #555;
  margin-top: 16px;
}

.billing-table {
  margin-top: 16px;
}

.billing-table-scroll {
  overflow-x: auto;
}

.billing-table table {
  width: 100%;
  border-collapse: collapse;
}

.billing-table th,
.billing-table td {
  padding: 8px;
  border: 1px solid #ddd;
  text-align: left;
  white-space: nowrap;
}

.billing-details dl {
  margin: 20px 0 0;
}

.billing-detail {
  display: grid;
  grid-template-columns: minmax(140px, 1fr) 2fr;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid #eee;
}

.billing-detail dt {
  font-weight: 600;
}

.billing-detail dd {
  margin: 0;
  overflow-wrap: anywhere;
}
.invoice-wrapper {
  max-width: 720px;
  margin: 0 auto;
  background-color: #ffffff;
}

.invoice-table td,
.invoice-table th {
  font-variant-numeric: tabular-nums;
}
</style>

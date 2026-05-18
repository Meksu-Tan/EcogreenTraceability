<template>
  <div class="section">
    <div class="section-header">
      <h1>WIP Transaction</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><router-link to="/dashboard">Dashboard</router-link></div>
        <div class="breadcrumb-item">Transactions</div>
        <div class="breadcrumb-item">WIP Entry</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row mb-4">
        <div class="col-md-5">
          <div class="card bg-dark text-white mb-0">
            <div class="card-body p-2">
              <select v-model="selectedSection" class="form-control">
                <option value="allSection">- All Section -</option>
                <option v-for="section in config" :key="section.id" :value="section.id">
                  - {{ section.title }} -
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <!-- Render all sections or specific ones -->
          <template v-for="section in filteredSections" :key="section.id">
            <div class="card bg-dark mb-4">
              <div class="card-body">
                <div class="text-center mb-4 mt-2">
                  <h5 class="text-white"><b>START OF {{ section.title }}</b></h5>
                </div>

                <!-- FEEDS -->
                <div v-if="section.feeds && section.feeds.length > 0" class="card mb-4">
                  <div class="card-body">
                    <template v-for="feed in section.feeds" :key="feed.id">
                      <div class="card bg-white mb-3 shadow-sm">
                        <div class="card-body">
                          <h4 class="text-center mb-3">
                            <span class="badge badge-light d-block text-dark" style="font-size: 18px;">{{ feed.title }}</span>
                          </h4>
                          <div class="d-flex justify-content-between mb-3">
                            <div class="text-muted font-weight-bold">LATEST LOG OF {{ feed.type }}</div>
                            <div>
                              <button class="btn btn-dark btn-sm mr-2" @click="openEntryModal(feed, 'feed')">
                                <i class="fas fa-edit"></i> {{ feed.type }}
                              </button>
                              <button class="btn btn-dark btn-sm mr-2" @click="openBalanceModal(feed.feedId, 'feed')">
                                <i class="fas fa-bars"></i> View Balance
                              </button>
                            </div>
                          </div>
                          
                          <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                              <thead class="bg-light">
                                <tr>
                                  <th>Feed Trace No</th>
                                  <th>Entry Date</th>
                                  <th>Matl Doc</th>
                                  <th>Sloc</th>
                                  <th>Total Material (MT)</th>
                                  <th>Total Supplier (MT)</th>
                                  <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr>
                                  <td colspan="7" class="text-center">Loading...</td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </template>
                  </div>
                </div>

                <div class="text-center mb-4 text-white">
                  <i class="fas fa-arrow-down mb-2" style="font-size: 24px;"></i><br>
                  <h5><b>PROCESS OF {{ section.title }}</b></h5>
                  <i class="fas fa-arrow-down mt-2" style="font-size: 24px;"></i>
                </div>

                <!-- RUNDOWNS -->
                <div v-if="section.rundowns && section.rundowns.length > 0" class="card mb-4" style="background-color: #324031;">
                  <div class="card-body">
                    <template v-for="rundown in section.rundowns" :key="rundown.id">
                      <div class="card bg-white mb-3 shadow-sm">
                        <div class="card-body">
                          <h4 class="text-center mb-3">
                            <span class="badge badge-light d-block text-dark" style="font-size: 18px;">{{ rundown.title }}</span>
                          </h4>
                          <div class="d-flex justify-content-between mb-3">
                            <div class="text-muted font-weight-bold">LATEST LOG OF {{ rundown.type }}</div>
                            <div>
                              <button class="btn btn-dark btn-sm mr-2" @click="openEntryModal(rundown, 'rundown')">
                                <i class="fas fa-edit"></i> {{ rundown.type }}
                              </button>
                              <button class="btn btn-dark btn-sm mr-2" @click="openBalanceModal(rundown.rundownId, 'rundown')">
                                <i class="fas fa-bars"></i> View Balance
                              </button>
                            </div>
                          </div>
                          
                          <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                              <thead class="bg-light">
                                <tr>
                                  <th>WIP Trace No</th>
                                  <th>Entry Date</th>
                                  <th>Matl Doc</th>
                                  <th>Sloc</th>
                                  <th>Total Material (MT)</th>
                                  <th>Total Supplier (MT)</th>
                                  <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr>
                                  <td colspan="7" class="text-center">Loading...</td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </template>
                  </div>
                </div>

                <div class="text-center mt-2 mb-2">
                  <h5 class="text-white"><b>END OF {{ section.title }}</b></h5>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { wipConfig } from '@/components/transaction/wip/wipConfig';
import { useTransactionWipStore } from '@/stores/transactionWip';

const store = useTransactionWipStore();
const config = ref(wipConfig);
const selectedSection = ref('allSection');

const filteredSections = computed(() => {
  if (selectedSection.value === 'allSection') return config.value;
  return config.value.filter(s => s.id === selectedSection.value);
});

const openEntryModal = (item, type) => {
  console.log('Open entry modal for', item, type);
};

const openBalanceModal = (id, type) => {
  console.log('Open balance modal for', id, type);
};
</script>

<style scoped>
.table th {
  background-color: #f4f6f9;
  color: #34395e;
  font-weight: 600;
}
</style>

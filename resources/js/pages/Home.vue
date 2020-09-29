<template>
    <div class="container">
        <section class="content-header">
            <h1>
                Dashboard
            </h1>
            <ol class="breadcrumb">
                <li><router-link :to="'/'"><i class="fa fa-dashboard"></i> Home</router-link></li>
                <li><a href="#">Dashboard</a></li>
            </ol>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <div class="row">

                              	<!-- FORM FILTER BERDASARKAN BULAN DAN TAHUN -->
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="">Bulan</label>
                                        <select v-model="month" class="form-control">
                                            <option value="01">Januari</option>
                                            <option value="02">Februari</option>
                                            <option value="03">Maret</option>
                                            <option value="04">April</option>
                                            <option value="05">Mei</option>
                                            <option value="06">Juni</option>
                                            <option value="07">Juli</option>
                                            <option value="08">Agustus</option>
                                            <option value="09">September</option>
                                            <option value="10">Oktober</option>
                                            <option value="11">November</option>
                                            <option value="12">Desember</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="">Tahun</label>
                                        <select v-model="year" class="form-control">
                                            <option v-for="(y, i) in years" :key="i" :value="y">{{ y }}</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- FORM FILTER BERDASARKAN BULAN DAN TAHUN -->

                                <!-- TOMBOL UNTUK EXPORT DATA KE EXCEL -->
                                <div class="col-md-2">
                                    <button class="btn btn-primary btn-sm pull-right" @click="exportData">Export</button>
                                </div>
                                <!-- TOMBOL UNTUK EXPORT DATA KE EXCEL -->
                            </div>
                        </div>
                        <div class="panel-body">
                            <!-- TAMPILKAN CHART DARI COMPONENT YANG SEBELUMNYA DIBUAT -->
                            <!-- DENGAN MENGIRIMKAN DATA, OPTIONS DAN LABELS SEBAGAI PROPS -->
                            <line-chart v-if="transactions.length > 0" :data="transaction_data" :options="chartOptions" :labels="labels"/>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
<script>
    import moment from 'moment'
    import _ from 'lodash'
    import LineChart from '../components/LineChart.vue' //IMPORT COMPONENT CHART
    import { mapActions, mapState } from 'vuex'

    export default {
        created() {
            //KETIKA HALAMAN INI DI-LOAD MAKA AKAN MEMINTA DATA KE SERVER
            //DAN MENGIRIMKAN PARAMETER BULAN DAN TAHUN YANG AKTIF
            this.getChartData({
                month: this.month,
                year: this.year
            })
        },
        data() {
            return {
                chartOptions: {
                    responsive: true,
                    maintainAspectRatio: false
                },
                month: moment().format('MM'), //DEFAULT BULAN YG AKTIF BERDASARKAN BULAN SAAT INI
                year: moment().format('Y') // //DEFAULT TAHUN YG AKTIF BERDASARKAN TAHUN SAAT INI
            }
        },
        watch: {
            //KETIKA VALUE BULAN BERUBAH, MAKA KITA REQUEST DATA BARU
            month() {
                this.getChartData({
                    month: this.month,
                    year: this.year
                })
            },
            //KETIKA VALUE TAHUN BERUBAH, MAKA KITA REQUEST DATA BARU
            year() {
                this.getChartData({
                    month: this.month,
                    year: this.year
                })
            }
        },
        computed: {
            ...mapState('dashboard', {
                transactions: state => state.transactions //AMBIL DATA DARI STATE
            }),
            //LIST TAHUN DARI 2010 SAMPAI TAHUN SAAT INI UNTUK DILOOPING DI FILTER TAG
            years() {
                return _.range(2010, moment().add(1, 'years').format('Y'))
            },
            //DATA LABELS YANG DITERIMA DARI SERVER
            labels() {
                //KARENA FORMAT DATANYA BERISI TOTAL DAN DATE, MAKA KITA FILTER HANYA AKAN MENGAMBIL DATENYA SAJA
                return _.map(this.transactions, function(o) {
                    return moment(o.date).format('DD')
                });
            },
            //DATA TOTAL TRANSAKSI YANG DITERIMA DARI SERVER
            transaction_data() {
                //KITA FILTER KARENA HANYA AKAN MENGAMBIL TOTAL VALUENYA SAJA
                return _.map(this.transactions, function(o) {
                    return o.total
                });
            }
        },
        methods: {
            ...mapActions('dashboard', ['getChartData']),
            //FUNGSI EXPORT DATA INI AKAN KITA KERJAKAN KEMUDIAN
            exportData() {
                 window.open(`/api/export?api_token=${this.token}&month=${this.month}&year=${this.year}`)
            }
        },
        components: { 'line-chart': LineChart }, //DEFINISIKAN CUSTOM TAG UNTUK COMPONENT YANG DIBUAT SEBELUMNYA
    }
</script>

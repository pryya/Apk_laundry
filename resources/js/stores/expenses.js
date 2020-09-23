import { reject } from 'lodash'
import $axios from '../api.js'

const state = () => ({
    expenses: [], // UNTUK MENAMPUNG DATA EXPENSES
    page: 1 // STATE UNTUK HALAMAN YANG SEDANG AKTIF
})

const mutations = {
    // ASSIGN DATA EXPENSES YANG DIDAPATKAN KE DALAM STATE
    ASSIGN_DATA(state, payload) {
        state.expenses = payload
    },
    // SET PAGE YANG AKTIF KE DALAM STATE PAGE
    SET_PAGE(state, payload) {
        state.page = payload
    }
}

const actions = {
    // FUNGSI UNTUK MENG-HANDLE REQUEST KE BACKEND
    getExpenses({ commit, state }, payload) {
        let search = typeof payload != 'undefined' ? payload : ''
        return new Promise((resolve, reject) => {
            // KIRIM PERMINTAAN KE BACKEND
            $axios.get(`\expenses?page=${state.page}&q=${search}`)
                .then((response) => {
                    // KETIKA RESPONSE NYA DIDAPATKAN, MAKA ASSIGN DATA TERSEBUT KE STATE
                    commit('ASSIGN_DATA', response.data)
                    resolve(response.data)
                })
        })
    }

}

export default {
    namespaced: true,
    state,
    actions,
    mutations
}
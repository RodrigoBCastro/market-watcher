import { createHttpClient } from './http'

export function createMarketApi(getToken) {
  const http = createHttpClient({ getToken })

  return {
    login: (credentials) => http.post('/auth/login', credentials),
    logout: () => http.post('/auth/logout', {}),
    me: () => http.get('/auth/me'),

    getDashboard: () => http.get('/dashboard'),

    getAssets: () => http.get('/assets'),
    createAsset: (payload) => http.post('/assets', payload),
    updateAsset: (id, payload) => http.patch(`/assets/${id}`, payload),
    deleteAsset: (id) => http.delete(`/assets/${id}`),

    syncAsset: (ticker) => http.post(`/sync/assets/${encodeURIComponent(ticker)}`, {}),
    syncAssets: () => http.post('/sync/assets', {}),
    syncMarket: () => http.post('/sync/market', {}),
    syncFull: () => http.post('/sync/full', {}),

    getAssetQuotes: (ticker, limit = 120) =>
      http.get(`/assets/${encodeURIComponent(ticker)}/quotes?limit=${limit}`),
    getAssetIndicators: (ticker, limit = 120) =>
      http.get(`/assets/${encodeURIComponent(ticker)}/indicators?limit=${limit}`),
    getAssetAnalysis: (ticker) => http.get(`/assets/${encodeURIComponent(ticker)}/analysis`),

    getOpportunitiesTop: (date = null) =>
      http.get(date ? `/opportunities/top?date=${date}` : '/opportunities/top'),
    getOpportunitiesAvoid: (date = null) =>
      http.get(date ? `/opportunities/avoid?date=${date}` : '/opportunities/avoid'),

    generateBrief: (date = null) => http.post('/briefs/generate', date ? { date } : {}),
    getBriefs: () => http.get('/briefs'),
    getBriefByDate: (date) => http.get(`/briefs/${date}`),
  }
}

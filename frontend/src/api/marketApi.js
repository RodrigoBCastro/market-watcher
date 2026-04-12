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

    getCalls: (status = null, limit = 100) =>
      http.get(status ? `/calls?status=${encodeURIComponent(status)}&limit=${limit}` : `/calls?limit=${limit}`),
    getCallQueue: (limit = 100) => http.get(`/calls/queue?limit=${limit}`),
    getCallById: (id) => http.get(`/calls/${id}`),
    getCallOutcomes: (limit = 100) => http.get(`/calls/outcomes?limit=${limit}`),
    generateCalls: (sync = false) => http.post(sync ? '/calls/generate?sync=1' : '/calls/generate', {}),
    evaluateOpenTrades: (sync = false) =>
      http.post(sync ? '/calls/evaluate-open?sync=1' : '/calls/evaluate-open', {}),
    approveCall: (id, comments = '') => http.post(`/calls/${id}/approve`, comments ? { comments } : {}),
    rejectCall: (id, comments = '') => http.post(`/calls/${id}/reject`, comments ? { comments } : {}),
    publishCall: (id) => http.post(`/calls/${id}/publish`, {}),

    getQuantDashboard: () => http.get('/quant/dashboard'),
    getQuantSetupMetrics: () => http.get('/quant/setup-metrics'),

    getBacktests: (limit = 30) => http.get(`/backtests?limit=${limit}`),
    runBacktest: (payload = {}) => http.post('/backtests/run', payload),

    getOptimizerCurrent: () => http.get('/optimizer/current'),
    runOptimizer: () => http.post('/optimizer/run', {}),
    applyOptimizerWeights: (payload) => http.post('/optimizer/apply', payload),
  }
}

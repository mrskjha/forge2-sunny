const BASE_URL = '/api';

function getToken() {
  return localStorage.getItem('token');
}

async function request(path, { method = 'GET', body, auth = true } = {}) {
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  if (auth) {
    const token = getToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
  }

  const config = { method, headers };

  if (body !== undefined) {
    config.body = JSON.stringify(body);
  }

  const res = await fetch(`${BASE_URL}${path}`, config);

  // Handle 401 — token expired or invalid
  if (res.status === 401) {
    localStorage.removeItem('token');
    window.location.href = '/login';
    throw new Error('Unauthorized');
  }

  const data = await res.json();

  if (!res.ok) {
    throw new Error(data.message || data.errors ? Object.values(data.errors).flat().join(', ') : 'Request failed');
  }

  return data;
}

export const api = {
  // Auth
  login: (email, password) => request('/login', { method: 'POST', body: { email, password }, auth: false }),
  register: (payload) => request('/register', { method: 'POST', body: payload, auth: false }),
  logout: () => request('/logout', { method: 'POST' }),

  // Tickets
  getTickets: () => request('/tickets'),
  getTicket: (id) => request(`/tickets/${id}`),
  createTicket: (payload) => request('/tickets', { method: 'POST', body: payload }),
  updateTicket: (id, payload) => request(`/tickets/${id}`, { method: 'PUT', body: payload }),
  deleteTicket: (id) => request(`/tickets/${id}`, { method: 'DELETE' }),

  // Replies
  addReply: (ticketId, body) => request(`/tickets/${ticketId}/replies`, { method: 'POST', body: { body } }),
};

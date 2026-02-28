import api from './axios';

/**
 * Autenticação
 */
export const register = (data) => api.post('/auth/register', data);
export const login = (email, password) => api.post('/auth/login', { email, password });
export const logout = () => api.post('/auth/logout');
export const getUser = () => api.get('/auth/me');

/**
 * Password Reset
 */
export const forgotPassword = (email) => api.post('/password/forgot', { email });
export const resetPassword = (token, email, password, password_confirmation) => 
  api.post('/password/reset', { token, email, password, password_confirmation });

/**
 * Email Verification
 */
export const resendVerificationEmail = () => api.post('/email/resend');
export const verifyEmail = (id, hash, expires, signature) => 
  api.get(`/email/verify/${id}/${hash}?expires=${expires}&signature=${signature}`);
export const getEmailVerificationStatus = () => api.get('/email/status');

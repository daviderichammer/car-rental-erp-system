import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import { useState, useEffect } from 'react'
import './App.css'

// Layout Components
import Layout from './components/Layout'
import LoginPage from './components/auth/LoginPage'

// Dashboard Components
import Dashboard from './components/dashboard/Dashboard'

// Feature Components
import ReservationsPage from './components/reservations/ReservationsPage'
import FleetPage from './components/fleet/FleetPage'
import CustomersPage from './components/customers/CustomersPage'
import ReportsPage from './components/reports/ReportsPage'
import SettingsPage from './components/settings/SettingsPage'

// Context for authentication and user state
import { AuthProvider, useAuth } from './contexts/AuthContext'

// Protected Route Component
function ProtectedRoute({ children }) {
  const { user, loading } = useAuth()
  
  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-primary"></div>
      </div>
    )
  }
  
  if (!user) {
    return <Navigate to="/login" replace />
  }
  
  return children
}

// Main App Component
function AppContent() {
  const { user } = useAuth()
  
  return (
    <Router>
      <div className="min-h-screen bg-background">
        <Routes>
          {/* Public Routes */}
          <Route 
            path="/login" 
            element={user ? <Navigate to="/dashboard" replace /> : <LoginPage />} 
          />
          
          {/* Protected Routes */}
          <Route path="/" element={<ProtectedRoute><Layout /></ProtectedRoute>}>
            <Route index element={<Navigate to="/dashboard" replace />} />
            <Route path="dashboard" element={<Dashboard />} />
            <Route path="reservations/*" element={<ReservationsPage />} />
            <Route path="fleet/*" element={<FleetPage />} />
            <Route path="customers/*" element={<CustomersPage />} />
            <Route path="reports/*" element={<ReportsPage />} />
            <Route path="settings/*" element={<SettingsPage />} />
          </Route>
          
          {/* Catch all route */}
          <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </div>
    </Router>
  )
}

function App() {
  return (
    <AuthProvider>
      <AppContent />
    </AuthProvider>
  )
}

export default App


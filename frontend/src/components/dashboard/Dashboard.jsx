import { useState, useEffect } from 'react'
import { 
  Car, 
  Calendar, 
  Users, 
  DollarSign,
  TrendingUp,
  TrendingDown,
  Clock,
  AlertTriangle
} from 'lucide-react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { useAuth } from '../../contexts/AuthContext'

// Mock data for dashboard
const mockStats = {
  totalVehicles: 45,
  availableVehicles: 32,
  activeReservations: 13,
  totalCustomers: 1247,
  monthlyRevenue: 125000,
  revenueChange: 12.5,
  utilizationRate: 71.1,
  maintenanceAlerts: 3
}

const mockRecentReservations = [
  {
    id: 'RES001',
    customer: 'John Smith',
    vehicle: '2023 Toyota Camry',
    status: 'confirmed',
    pickupDate: '2024-07-18',
    amount: 450
  },
  {
    id: 'RES002',
    customer: 'Sarah Johnson',
    vehicle: '2023 Honda CR-V',
    status: 'in_progress',
    pickupDate: '2024-07-17',
    amount: 680
  },
  {
    id: 'RES003',
    customer: 'Mike Davis',
    vehicle: '2023 BMW X3',
    status: 'pending',
    pickupDate: '2024-07-19',
    amount: 890
  }
]

const mockMaintenanceAlerts = [
  {
    id: 1,
    vehicle: '2022 Toyota Corolla - ABC123',
    type: 'Oil Change',
    dueDate: '2024-07-20',
    priority: 'medium'
  },
  {
    id: 2,
    vehicle: '2021 Honda Civic - XYZ789',
    type: 'Tire Rotation',
    dueDate: '2024-07-18',
    priority: 'high'
  },
  {
    id: 3,
    vehicle: '2023 Ford Escape - DEF456',
    type: 'Brake Inspection',
    dueDate: '2024-07-22',
    priority: 'low'
  }
]

export default function Dashboard() {
  const { user } = useAuth()
  const [stats, setStats] = useState(mockStats)
  const [recentReservations, setRecentReservations] = useState(mockRecentReservations)
  const [maintenanceAlerts, setMaintenanceAlerts] = useState(mockMaintenanceAlerts)

  const getStatusBadge = (status) => {
    const statusClasses = {
      confirmed: 'status-confirmed',
      in_progress: 'status-in-progress',
      pending: 'status-pending',
      completed: 'status-completed',
      cancelled: 'status-cancelled'
    }
    
    return (
      <Badge className={`${statusClasses[status]} text-xs`}>
        {status.replace('_', ' ').toUpperCase()}
      </Badge>
    )
  }

  const getPriorityBadge = (priority) => {
    const priorityClasses = {
      high: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
      medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
      low: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    }
    
    return (
      <Badge className={`${priorityClasses[priority]} text-xs`}>
        {priority.toUpperCase()}
      </Badge>
    )
  }

  return (
    <div className="space-y-6">
      {/* Welcome Header */}
      <div>
        <h1 className="text-3xl font-bold text-foreground">
          Welcome back, {user?.first_name}!
        </h1>
        <p className="text-muted-foreground mt-1">
          Here's what's happening with your car rental business today.
        </p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Total Vehicles */}
        <Card className="card-hover">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Vehicles</CardTitle>
            <Car className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.totalVehicles}</div>
            <p className="text-xs text-muted-foreground">
              {stats.availableVehicles} available
            </p>
          </CardContent>
        </Card>

        {/* Active Reservations */}
        <Card className="card-hover">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Reservations</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.activeReservations}</div>
            <p className="text-xs text-muted-foreground">
              {stats.utilizationRate}% utilization
            </p>
          </CardContent>
        </Card>

        {/* Total Customers */}
        <Card className="card-hover">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Customers</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.totalCustomers.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">
              Registered users
            </p>
          </CardContent>
        </Card>

        {/* Monthly Revenue */}
        <Card className="card-hover">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Monthly Revenue</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">${stats.monthlyRevenue.toLocaleString()}</div>
            <p className="text-xs text-success flex items-center">
              <TrendingUp className="h-3 w-3 mr-1" />
              +{stats.revenueChange}% from last month
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Main Content Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Reservations */}
        <Card>
          <CardHeader>
            <CardTitle>Recent Reservations</CardTitle>
            <CardDescription>Latest booking activity</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {recentReservations.map((reservation) => (
                <div key={reservation.id} className="flex items-center justify-between p-3 border rounded-lg">
                  <div className="flex-1">
                    <div className="flex items-center justify-between mb-1">
                      <p className="font-medium text-sm">{reservation.customer}</p>
                      {getStatusBadge(reservation.status)}
                    </div>
                    <p className="text-sm text-muted-foreground">{reservation.vehicle}</p>
                    <p className="text-xs text-muted-foreground">
                      Pickup: {new Date(reservation.pickupDate).toLocaleDateString()}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="font-medium">${reservation.amount}</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Maintenance Alerts */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center">
              <AlertTriangle className="h-5 w-5 mr-2 text-warning" />
              Maintenance Alerts
            </CardTitle>
            <CardDescription>Vehicles requiring attention</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {maintenanceAlerts.map((alert) => (
                <div key={alert.id} className="flex items-center justify-between p-3 border rounded-lg">
                  <div className="flex-1">
                    <div className="flex items-center justify-between mb-1">
                      <p className="font-medium text-sm">{alert.type}</p>
                      {getPriorityBadge(alert.priority)}
                    </div>
                    <p className="text-sm text-muted-foreground">{alert.vehicle}</p>
                    <p className="text-xs text-muted-foreground flex items-center">
                      <Clock className="h-3 w-3 mr-1" />
                      Due: {new Date(alert.dueDate).toLocaleDateString()}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}


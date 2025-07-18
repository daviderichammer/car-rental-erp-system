import { NavLink } from 'react-router-dom'
import { 
  LayoutDashboard, 
  Calendar, 
  Car, 
  Users, 
  BarChart3, 
  Settings,
  X,
  Building2,
  Wrench,
  CreditCard,
  FileText
} from 'lucide-react'
import { useAuth } from '../../contexts/AuthContext'
import { Button } from '@/components/ui/button'

const navigationItems = [
  {
    name: 'Dashboard',
    href: '/dashboard',
    icon: LayoutDashboard,
    roles: ['admin', 'manager', 'agent', 'customer_service']
  },
  {
    name: 'Reservations',
    href: '/reservations',
    icon: Calendar,
    roles: ['admin', 'manager', 'agent', 'customer_service']
  },
  {
    name: 'Fleet',
    href: '/fleet',
    icon: Car,
    roles: ['admin', 'manager', 'fleet_manager']
  },
  {
    name: 'Customers',
    href: '/customers',
    icon: Users,
    roles: ['admin', 'manager', 'agent', 'customer_service']
  },
  {
    name: 'Locations',
    href: '/locations',
    icon: Building2,
    roles: ['admin', 'manager']
  },
  {
    name: 'Maintenance',
    href: '/maintenance',
    icon: Wrench,
    roles: ['admin', 'manager', 'fleet_manager', 'maintenance']
  },
  {
    name: 'Financial',
    href: '/financial',
    icon: CreditCard,
    roles: ['admin', 'manager', 'financial']
  },
  {
    name: 'Reports',
    href: '/reports',
    icon: BarChart3,
    roles: ['admin', 'manager', 'financial']
  },
  {
    name: 'Settings',
    href: '/settings',
    icon: Settings,
    roles: ['admin', 'manager']
  }
]

export default function Sidebar({ mobile = false, onClose }) {
  const { user } = useAuth()

  // Filter navigation items based on user role
  const filteredNavigation = navigationItems.filter(item => 
    item.roles.includes(user?.role) || user?.role === 'admin'
  )

  return (
    <div className={`${mobile ? 'bg-white dark:bg-gray-900' : 'desktop-sidebar'} flex flex-col h-full`}>
      {/* Header */}
      <div className="flex items-center justify-between p-4 border-b border-sidebar-border">
        <div className="flex items-center space-x-2">
          <div className="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
            <Car className="w-5 h-5 text-white" />
          </div>
          <div>
            <h1 className="text-lg font-bold text-sidebar-foreground">CarRental</h1>
            <p className="text-xs text-sidebar-foreground/70">ERP System</p>
          </div>
        </div>
        
        {mobile && (
          <Button
            variant="ghost"
            size="sm"
            onClick={onClose}
            className="text-sidebar-foreground hover:bg-sidebar-accent"
          >
            <X className="w-5 h-5" />
          </Button>
        )}
      </div>

      {/* User Info */}
      <div className="p-4 border-b border-sidebar-border">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 bg-sidebar-primary rounded-full flex items-center justify-center">
            <span className="text-sm font-medium text-sidebar-primary-foreground">
              {user?.first_name?.[0]}{user?.last_name?.[0]}
            </span>
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-sidebar-foreground truncate">
              {user?.first_name} {user?.last_name}
            </p>
            <p className="text-xs text-sidebar-foreground/70 truncate">
              {user?.role?.replace('_', ' ').toUpperCase()}
            </p>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 p-4 space-y-1">
        {filteredNavigation.map((item) => {
          const Icon = item.icon
          return (
            <NavLink
              key={item.name}
              to={item.href}
              className={({ isActive }) =>
                `flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                  isActive
                    ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                    : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                }`
              }
            >
              <Icon className="w-5 h-5" />
              <span>{item.name}</span>
            </NavLink>
          )
        })}
      </nav>

      {/* Footer */}
      <div className="p-4 border-t border-sidebar-border">
        <div className="text-xs text-sidebar-foreground/50 text-center">
          Car Rental ERP v1.0
        </div>
      </div>
    </div>
  )
}


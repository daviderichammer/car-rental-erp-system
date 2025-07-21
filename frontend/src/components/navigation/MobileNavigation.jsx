import { NavLink } from 'react-router-dom'
import { 
  LayoutDashboard, 
  Calendar, 
  Car, 
  Users, 
  BarChart3
} from 'lucide-react'
import { useAuth } from '../../contexts/AuthContext'

const mobileNavItems = [
  {
    name: 'Dashboard',
    href: '/dashboard',
    icon: LayoutDashboard,
    roles: ['admin', 'manager', 'agent', 'customer_service']
  },
  {
    name: 'Bookings',
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
    name: 'Reports',
    href: '/reports',
    icon: BarChart3,
    roles: ['admin', 'manager', 'financial']
  }
]

export default function MobileNavigation() {
  const { user } = useAuth()

  // Filter navigation items based on user role
  const filteredNavigation = mobileNavItems.filter(item => 
    item.roles.includes(user?.role) || user?.role === 'admin'
  ).slice(0, 5) // Limit to 5 items for mobile

  return (
    <nav className="mobile-nav">
      <div className="flex">
        {filteredNavigation.map((item) => {
          const Icon = item.icon
          return (
            <NavLink
              key={item.name}
              to={item.href}
              className={({ isActive }) =>
                `mobile-nav-item transition-colors ${
                  isActive
                    ? 'text-primary bg-primary/10'
                    : 'text-muted-foreground hover:text-foreground'
                }`
              }
            >
              <Icon className="w-5 h-5 mb-1" />
              <span className="text-xs font-medium">{item.name}</span>
            </NavLink>
          )
        })}
      </div>
    </nav>
  )
}


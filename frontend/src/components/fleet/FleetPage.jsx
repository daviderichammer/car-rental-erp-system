import { Car, Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

export default function FleetPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">Fleet Management</h1>
          <p className="text-muted-foreground mt-1">
            Manage vehicles, categories, and fleet operations
          </p>
        </div>
        <Button>
          <Plus className="w-4 h-4 mr-2" />
          Add Vehicle
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center">
            <Car className="w-5 h-5 mr-2" />
            Fleet Management
          </CardTitle>
          <CardDescription>
            Comprehensive vehicle and fleet management interface
          </CardDescription>
        </CardHeader>
        <CardContent>
          <p className="text-muted-foreground">
            Features coming soon:
          </p>
          <ul className="list-disc list-inside mt-2 space-y-1 text-sm text-muted-foreground">
            <li>Vehicle inventory management</li>
            <li>Vehicle categories and features</li>
            <li>Availability tracking</li>
            <li>Maintenance scheduling</li>
            <li>GPS tracking integration</li>
          </ul>
        </CardContent>
      </Card>
    </div>
  )
}


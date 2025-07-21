import { BarChart3, Download } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

export default function ReportsPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">Reports & Analytics</h1>
          <p className="text-muted-foreground mt-1">
            Business intelligence and performance analytics
          </p>
        </div>
        <Button>
          <Download className="w-4 h-4 mr-2" />
          Export Report
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center">
            <BarChart3 className="w-5 h-5 mr-2" />
            Analytics Dashboard
          </CardTitle>
          <CardDescription>
            Comprehensive business analytics and reporting tools
          </CardDescription>
        </CardHeader>
        <CardContent>
          <p className="text-muted-foreground">
            Features coming soon:
          </p>
          <ul className="list-disc list-inside mt-2 space-y-1 text-sm text-muted-foreground">
            <li>Revenue and financial reports</li>
            <li>Fleet utilization analytics</li>
            <li>Customer behavior insights</li>
            <li>Operational performance metrics</li>
            <li>Custom report builder</li>
          </ul>
        </CardContent>
      </Card>
    </div>
  )
}


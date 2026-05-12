import 'package:flutter/material.dart';
import '../../features/lab/presentation/screens/lab_dashboard_screen.dart';
import '../../features/lab/presentation/screens/lab_orders_screen.dart';

class AppRouter {
  static Route<dynamic> generateRoute(RouteSettings settings) {
    switch (settings.name) {
      case '/':
      case '/lab/dashboard':
        return MaterialPageRoute(
          builder: (_) => const LabDashboardScreen(),
          settings: settings,
        );
      case '/lab/orders':
        return MaterialPageRoute(
          builder: (_) => const LabOrdersScreen(),
          settings: settings,
        );
      default:
        return MaterialPageRoute(
          builder: (_) => const LabDashboardScreen(),
        );
    }
  }
}

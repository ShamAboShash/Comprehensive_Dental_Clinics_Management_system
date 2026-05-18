import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'core/theme/app_theme.dart';
import 'core/router/app_router.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const DTTeethApp());
}

class DTTeethApp extends StatelessWidget {
  const DTTeethApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ScreenUtilInit(
      // Design frame: 1440×900 (standard desktop)
      designSize: const Size(1440, 900),
      minTextAdapt: true,
      splitScreenMode: true,
      builder: (context, child) {
        return MaterialApp(
          title: 'DT.Teeth — نظام إدارة مركز طب الأسنان',
          debugShowCheckedModeBanner: false,
          theme: AppTheme.darkTheme,
          initialRoute: '/',
          onGenerateRoute: AppRouter.generateRoute,
          builder: (context, widget) {
            // Ensure RTL direction globally
            return Directionality(
              textDirection: TextDirection.rtl,
              child: widget ?? const SizedBox.shrink(),
            );
          },
        );
      },
    );
  }
}

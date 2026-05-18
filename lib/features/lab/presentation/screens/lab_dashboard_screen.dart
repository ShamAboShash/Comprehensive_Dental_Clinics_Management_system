import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_sizes.dart';
import '../../../../core/theme/app_text_styles.dart';
import '../../../../core/shared_widgets/app_sidebar.dart';
import '../../../../core/shared_widgets/app_topbar.dart';
import '../widgets/stat_card.dart';
import '../widgets/order_row_widget.dart';

class LabDashboardScreen extends StatefulWidget {
  const LabDashboardScreen({super.key});

  @override
  State<LabDashboardScreen> createState() => _LabDashboardScreenState();
}

class _LabDashboardScreenState extends State<LabDashboardScreen> {
  String _activeRoute = '/lab/dashboard';

  final List<SidebarItem> _sidebarItems = const [
    SidebarItem(
        label: 'الصفحة الرئيسية',
        icon: Icons.home_rounded,
        route: '/lab/dashboard'),
    SidebarItem(
        label: 'الملف الشخصي',
        icon: Icons.person_outline_rounded,
        route: '/lab/profile'),
    SidebarItem(
        label: 'المواعيد',
        icon: Icons.calendar_month_outlined,
        route: '/lab/appointments'),
    SidebarItem(
        label: 'المرضى',
        icon: Icons.people_outline_rounded,
        route: '/lab/patients'),
    SidebarItem(
        label: 'الطلبيات',
        icon: Icons.shopping_cart_outlined,
        route: '/lab/orders',
        badge: 3),
    SidebarItem(
        label: 'الموظفون',
        icon: Icons.badge_outlined,
        route: '/lab/employees'),
    SidebarItem(
        label: 'التقارير',
        icon: Icons.description_outlined,
        route: '/lab/reports'),
    SidebarItem(
        label: 'عمليات الجرد',
        icon: Icons.inventory_2_outlined,
        route: '/lab/inventory'),
    SidebarItem(
        label: 'الأطباء',
        icon: Icons.medical_services_outlined,
        route: '/lab/doctors'),
  ];

  // ===== Mock Orders =====
  final List<Map<String, dynamic>> _todayOrders = [
    {
      'id': 'LAB-747',
      'doctor': 'د. تجربة',
      'type': 'تلبيسة',
      'patient': 'البشيرة',
      'date': '27-05-2025',
      'technician': null,
      'status': 'جديد',
      'statusColor': AppColors.statusNew,
      'urgent': false,
    },
    {
      'id': 'LAB-748',
      'doctor': 'د. أحمد',
      'type': 'جسر',
      'patient': 'Zircona',
      'date': '08-09-2025',
      'technician': 'فني 1',
      'status': 'قيد التصنيع',
      'statusColor': AppColors.statusManufacturing,
      'urgent': false,
    },
    {
      'id': 'LAB-552',
      'doctor': 'د. أحمد',
      'type': 'تلبيسة',
      'patient': 'Metal',
      'date': '08-09-2025',
      'technician': 'فني 2',
      'status': 'جاهز',
      'statusColor': AppColors.statusReady,
      'urgent': true,
    },
  ];

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.background,
        body: Row(
          children: [
            // ===== Sidebar =====
            AppSidebar(
              activeRoute: _activeRoute,
              onNavigate: (route) {
                setState(() => _activeRoute = route);
                if (route == '/lab/orders') {
                  Navigator.pushNamed(context, '/lab/orders');
                }
              },
              items: _sidebarItems,
              systemName: 'نظام المخبر',
              systemColor: AppColors.labColor,
            ),

            // ===== Main Content =====
            Expanded(
              child: Column(
                children: [
                  // Topbar
                  const AppTopbar(
                    title: 'لوحة التحكم',
                    subtitle: 'مرحبًا بك في نظام المخبر',
                    notificationCount: 5,
                  ),
                  // Content
                  Expanded(
                    child: SingleChildScrollView(
                      padding: EdgeInsets.all(20.w),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Welcome Row
                          _buildWelcomeRow(),
                          SizedBox(height: 20.h),
                          // Stats Row
                          _buildStatsRow(),
                          SizedBox(height: 20.h),
                          // Bottom: Orders + Mini Chart
                          _buildBottomSection(),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ===== Welcome / KPI Row =====
  Widget _buildWelcomeRow() {
    return Container(
      padding: EdgeInsets.all(20.w),
      decoration: BoxDecoration(
        color: AppColors.surfaceCard,
        borderRadius: BorderRadius.circular(AppSizes.radiusLG),
        border: Border.all(color: AppColors.borderDefault),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'مرحباً بك في نظام المخبر',
                  style: AppTextStyles.headlineMedium
                      .copyWith(fontSize: 16.sp),
                ),
                SizedBox(height: 4.h),
                Text(
                  'نظرة عامة على أداء المخبر اليوم',
                  style: AppTextStyles.bodyMedium,
                ),
              ],
            ),
          ),
          // Mini KPI badges
          _buildKpiBadge('96%', 'نسبة الإنجاز', AppColors.success),
          SizedBox(width: 12.w),
          _buildKpiBadge('5', 'فنيين نشطين', AppColors.accent),
          SizedBox(width: 12.w),
          _buildKpiBadge('1', 'طلب عاجل', AppColors.error),
          SizedBox(width: 12.w),
          _buildKpiBadge('1', 'طلب جديد', AppColors.info),
        ],
      ),
    );
  }

  Widget _buildKpiBadge(String value, String label, Color color) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 14.w, vertical: 10.h),
      decoration: BoxDecoration(
        color: color.withOpacity(0.10),
        borderRadius: BorderRadius.circular(AppSizes.radiusMD),
        border: Border.all(color: color.withOpacity(0.25), width: 1),
      ),
      child: Column(
        children: [
          Text(
            value,
            style: TextStyle(
              fontFamily: 'Cairo',
              fontSize: 20.sp,
              fontWeight: FontWeight.w900,
              color: color,
            ),
          ),
          SizedBox(height: 2.h),
          Text(
            label,
            style: AppTextStyles.labelSmall.copyWith(
              color: color.withOpacity(0.8),
              fontSize: 9.sp,
            ),
          ),
        ],
      ),
    );
  }

  // ===== Stats Cards Row =====
  Widget _buildStatsRow() {
    return Row(
      children: [
        Expanded(
          child: StatCard(
            title: 'إجمالي الطلبات',
            value: '2',
            subtitle: 'اليوم',
            color: AppColors.accent,
            icon: Icons.assignment_rounded,
          ),
        ),
        SizedBox(width: 12.w),
        Expanded(
          child: StatCard(
            title: 'قيد التصنيع',
            value: '23',
            subtitle: 'طلب',
            color: AppColors.warning,
            icon: Icons.engineering_rounded,
          ),
        ),
        SizedBox(width: 12.w),
        Expanded(
          child: StatCard(
            title: 'تم التسليم',
            value: '7',
            subtitle: 'هذا الأسبوع',
            color: AppColors.success,
            icon: Icons.check_circle_rounded,
          ),
        ),
        SizedBox(width: 12.w),
        Expanded(
          child: StatCard(
            title: 'الفنيون النشطون',
            value: '4',
            subtitle: 'من 6',
            color: AppColors.secondary,
            icon: Icons.people_rounded,
          ),
        ),
      ],
    );
  }

  // ===== Bottom: Orders List =====
  Widget _buildBottomSection() {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // ===== Orders List =====
        Expanded(
          flex: 3,
          child: _buildOrdersTable(),
        ),
        SizedBox(width: 16.w),
        // ===== Mini Stats Panel =====
        SizedBox(width: 200.w, child: _buildMiniStatsPanel()),
      ],
    );
  }

  Widget _buildOrdersTable() {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surfaceCard,
        borderRadius: BorderRadius.circular(AppSizes.radiusLG),
        border: Border.all(color: AppColors.borderDefault),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Padding(
            padding:
                EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('طلبات اليوم',
                    style: AppTextStyles.headlineSmall
                        .copyWith(fontSize: 14.sp)),
                Container(
                  padding: EdgeInsets.symmetric(
                      horizontal: 10.w, vertical: 4.h),
                  decoration: BoxDecoration(
                    color: AppColors.warning.withOpacity(0.15),
                    borderRadius:
                        BorderRadius.circular(AppSizes.radiusFull),
                    border: Border.all(
                        color: AppColors.warning.withOpacity(0.3)),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.calendar_today_rounded,
                          size: 11.sp, color: AppColors.warning),
                      SizedBox(width: 4.w),
                      Text(
                        'طلبيات اليوم',
                        style: TextStyle(
                          fontFamily: 'Cairo',
                          fontSize: 10.sp,
                          fontWeight: FontWeight.w700,
                          color: AppColors.warning,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Divider(height: 1, color: AppColors.borderDefault),
          // Column Headers
          Padding(
            padding: EdgeInsets.symmetric(
                horizontal: 16.w, vertical: 8.h),
            child: Row(
              children: [
                _tableHeader('رقم الطلب', flex: 2),
                _tableHeader('الطبيب', flex: 2),
                _tableHeader('النوع', flex: 2),
                _tableHeader('المريض/المادة', flex: 2),
                _tableHeader('الموعد', flex: 2),
                _tableHeader('الفني', flex: 2),
                _tableHeader('الحالة', flex: 2),
                _tableHeader('إجراءات', flex: 1),
              ],
            ),
          ),
          Divider(height: 1, color: AppColors.borderDefault),
          // Rows
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: _todayOrders.length,
            separatorBuilder: (_, __) =>
                Divider(height: 1, color: AppColors.borderDefault),
            itemBuilder: (context, index) {
              final order = _todayOrders[index];
              return OrderRowWidget(order: order);
            },
          ),
        ],
      ),
    );
  }

  Widget _tableHeader(String text, {int flex = 1}) {
    return Expanded(
      flex: flex,
      child: Text(
        text,
        style: AppTextStyles.labelSmall.copyWith(
          color: AppColors.textMuted,
          fontSize: 10.sp,
        ),
        textAlign: TextAlign.center,
      ),
    );
  }

  // ===== Mini Stats Panel =====
  Widget _buildMiniStatsPanel() {
    return Column(
      children: [
        _miniStatCard('طلبيات اليوم', '2', Icons.today_rounded,
            AppColors.accent),
        SizedBox(height: 10.h),
        _miniStatCard('إيرادات اليوم', '450\$', Icons.attach_money_rounded,
            AppColors.success),
        SizedBox(height: 10.h),
        _miniStatCard('الفنيون المتاحون', '4', Icons.people_rounded,
            AppColors.secondary),
        SizedBox(height: 10.h),
        // Completion chart placeholder
        _buildCompletionCard(),
      ],
    );
  }

  Widget _miniStatCard(
      String label, String value, IconData icon, Color color) {
    return Container(
      padding: EdgeInsets.all(14.w),
      decoration: BoxDecoration(
        color: AppColors.surfaceCard,
        borderRadius: BorderRadius.circular(AppSizes.radiusLG),
        border: Border.all(color: AppColors.borderDefault),
      ),
      child: Row(
        children: [
          Container(
            width: 34.w,
            height: 34.w,
            decoration: BoxDecoration(
              color: color.withOpacity(0.12),
              borderRadius: BorderRadius.circular(AppSizes.radiusMD),
            ),
            child: Icon(icon, size: 17.sp, color: color),
          ),
          SizedBox(width: 10.w),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(value,
                    style: TextStyle(
                      fontFamily: 'Cairo',
                      fontSize: 18.sp,
                      fontWeight: FontWeight.w900,
                      color: AppColors.textPrimary,
                    )),
                Text(label,
                    style: AppTextStyles.bodySmall
                        .copyWith(fontSize: 9.5.sp)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCompletionCard() {
    return Container(
      padding: EdgeInsets.all(14.w),
      decoration: BoxDecoration(
        color: AppColors.surfaceCard,
        borderRadius: BorderRadius.circular(AppSizes.radiusLG),
        border: Border.all(color: AppColors.borderDefault),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('نسبة الإنجاز',
              style: AppTextStyles.labelMedium.copyWith(fontSize: 10.sp)),
          SizedBox(height: 10.h),
          Stack(
            children: [
              Container(
                height: 8.h,
                decoration: BoxDecoration(
                  color: AppColors.borderDefault,
                  borderRadius: BorderRadius.circular(AppSizes.radiusFull),
                ),
              ),
              FractionallySizedBox(
                widthFactor: 0.96,
                child: Container(
                  height: 8.h,
                  decoration: BoxDecoration(
                    gradient: AppColors.primaryGradient,
                    borderRadius:
                        BorderRadius.circular(AppSizes.radiusFull),
                  ),
                ),
              ),
            ],
          ),
          SizedBox(height: 6.h),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('96%',
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    fontSize: 14.sp,
                    fontWeight: FontWeight.w900,
                    color: AppColors.accent,
                  )),
              Text('هذا الشهر',
                  style:
                      AppTextStyles.bodySmall.copyWith(fontSize: 9.sp)),
            ],
          ),
        ],
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_sizes.dart';
import '../../../../core/theme/app_text_styles.dart';
import '../../../../core/shared_widgets/app_sidebar.dart';
import '../../../../core/shared_widgets/app_topbar.dart';
import '../widgets/order_row_widget.dart';

class LabOrdersScreen extends StatefulWidget {
  const LabOrdersScreen({super.key});

  @override
  State<LabOrdersScreen> createState() => _LabOrdersScreenState();
}

class _LabOrdersScreenState extends State<LabOrdersScreen> {
  String _activeRoute = '/lab/orders'; // matches 'الطلبيات'
  String _selectedFilter = 'الكل';

  final List<String> _filters = [
    'الكل',
    'جديد',
    'قيد التصنيع',
    'جاهز',
    'تم التسليم',
  ];

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

  // ===== Full Orders Mock Data =====
  final List<Map<String, dynamic>> _allOrders = [
    {
      'id': 'LAB-745',
      'doctor': 'د. تجربة',
      'type': 'تلبيسة',
      'patient': 'البشيرة',
      'date': '2025-09-27',
      'technician': null,
      'status': 'جديد',
      'statusColor': AppColors.statusNew,
      'urgent': false,
    },
    {
      'id': 'PFM-76',
      'doctor': null,
      'type': null,
      'patient': null,
      'date': '2026-04-28',
      'technician': 'فني 1',
      'status': 'بنم',
      'statusColor': AppColors.statusManufacturing,
      'urgent': false,
      'subRows': [
        {
          'id': 'LAB-746',
          'doctor': 'د. أحمد',
          'type': 'جسر',
          'patient': 'Zircona',
          'date': '2026-04-28',
          'technician': 'فني 1',
          'status': 'قيد التصنيع',
          'statusColor': AppColors.statusManufacturing,
          'urgent': false,
        }
      ],
    },
    {
      'id': 'LAB-143',
      'doctor': 'د. أحمد',
      'type': 'تلبيسة',
      'patient': 'PFM',
      'date': '2026-04-28',
      'technician': 'فني 2',
      'status': 'بنم',
      'statusColor': AppColors.statusManufacturing,
      'urgent': false,
    },
    {
      'id': 'LAB-143',
      'doctor': 'د. أحمد',
      'type': 'تلبيسة',
      'patient': 'Metal',
      'date': '2026-04-30',
      'technician': null,
      'status': 'جديد',
      'statusColor': AppColors.statusNew,
      'urgent': true,
    },
    {
      'id': 'LAB-182',
      'doctor': 'د. أحمد',
      'type': 'تلبيسة',
      'patient': 'HFB',
      'date': '2026-05-07',
      'technician': null,
      'status': 'جديد',
      'statusColor': AppColors.statusNew,
      'urgent': false,
    },
    {
      'id': 'RCT-50',
      'doctor': null,
      'type': null,
      'patient': null,
      'date': null,
      'technician': null,
      'status': null,
      'statusColor': AppColors.statusNew,
      'urgent': false,
      'isGroupHeader': true,
    },
  ];

  List<Map<String, dynamic>> get _filteredOrders {
    if (_selectedFilter == 'الكل') return _allOrders;
    return _allOrders
        .where((o) => o['status'] == _selectedFilter)
        .toList();
  }

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
                if (route == '/lab/dashboard') {
                  Navigator.pushNamedAndRemoveUntil(
                      context, '/lab/dashboard', (_) => false);
                }
              },
              items: _sidebarItems,
              systemName: 'نظام المخبر',
            ),

            // ===== Main Content =====
            Expanded(
              child: Column(
                children: [
                  const AppTopbar(
                    title: 'طلبيات الأطباء',
                    subtitle: 'إدارة وتتبع جميع الطلبات',
                    notificationCount: 5,
                  ),
                  Expanded(
                    child: SingleChildScrollView(
                      padding: EdgeInsets.all(20.w),
                      child: Column(
                        children: [
                          // Filters + Add Button Row
                          _buildFiltersRow(),
                          SizedBox(height: 16.h),
                          // Orders Table
                          _buildOrdersTable(),
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

  // ===== Filters Row =====
  Widget _buildFiltersRow() {
    return Row(
      children: [
        // Filter chips
        Expanded(
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: _filters
                  .map((filter) => _buildFilterChip(filter))
                  .toList(),
            ),
          ),
        ),
        // Add button
        ElevatedButton.icon(
          onPressed: () {},
          icon: Icon(Icons.add_rounded, size: 16.sp),
          label: const Text('إضافة طلب'),
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.accent,
            foregroundColor: AppColors.primaryDark,
            textStyle: TextStyle(
              fontFamily: 'Cairo',
              fontSize: 12.sp,
              fontWeight: FontWeight.w800,
            ),
            padding:
                EdgeInsets.symmetric(horizontal: 14.w, vertical: 10.h),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppSizes.radiusMD),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildFilterChip(String label) {
    final isSelected = _selectedFilter == label;
    return GestureDetector(
      onTap: () => setState(() => _selectedFilter = label),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: EdgeInsets.only(left: 8.w),
        padding:
            EdgeInsets.symmetric(horizontal: 12.w, vertical: 7.h),
        decoration: BoxDecoration(
          color: isSelected
              ? AppColors.accent.withOpacity(0.15)
              : AppColors.surfaceCard,
          borderRadius: BorderRadius.circular(AppSizes.radiusFull),
          border: Border.all(
            color: isSelected
                ? AppColors.accent.withOpacity(0.5)
                : AppColors.borderDefault,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 11.sp,
            fontWeight: FontWeight.w700,
            color: isSelected ? AppColors.accent : AppColors.textTertiary,
          ),
        ),
      ),
    );
  }

  // ===== Orders Table =====
  Widget _buildOrdersTable() {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surfaceCard,
        borderRadius: BorderRadius.circular(AppSizes.radiusLG),
        border: Border.all(color: AppColors.borderDefault),
      ),
      child: Column(
        children: [
          // Table Header
          Container(
            padding:
                EdgeInsets.symmetric(horizontal: 16.w, vertical: 10.h),
            decoration: const BoxDecoration(
              border: Border(
                  bottom: BorderSide(color: AppColors.borderDefault)),
            ),
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
          // Rows
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: _filteredOrders.length,
            separatorBuilder: (_, __) =>
                const Divider(height: 1, color: AppColors.borderDefault),
            itemBuilder: (context, index) {
              final order = _filteredOrders[index];
              // Group header row
              if (order['isGroupHeader'] == true) {
                return _buildGroupHeaderRow(order['id']);
              }
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

  Widget _buildGroupHeaderRow(String id) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 10.h),
      color: AppColors.accent.withOpacity(0.04),
      child: Row(
        children: [
          Icon(Icons.expand_more_rounded,
              size: 14.sp, color: AppColors.textTertiary),
          SizedBox(width: 6.w),
          Text(
            id,
            style: TextStyle(
              fontFamily: 'Cairo',
              fontSize: 11.sp,
              fontWeight: FontWeight.w700,
              color: AppColors.accent.withOpacity(0.6),
            ),
          ),
        ],
      ),
    );
  }
}

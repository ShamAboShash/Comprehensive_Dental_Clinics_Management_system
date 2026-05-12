import 'package:flutter/material.dart';
import '../theme/app_colors.dart';
import '../theme/app_sizes.dart';
import '../theme/app_text_styles.dart';

class SidebarItem {
  final String label;
  final IconData icon;
  final String route;
  final int? badge;

  const SidebarItem({
    required this.label,
    required this.icon,
    required this.route,
    this.badge,
  });
}

class AppSidebar extends StatelessWidget {
  final String activeRoute;
  final void Function(String route) onNavigate;
  final List<SidebarItem> items;
  final String systemName;
  final Color systemColor;

  const AppSidebar({
    super.key,
    required this.activeRoute,
    required this.onNavigate,
    required this.items,
    required this.systemName,
    this.systemColor = AppColors.accent,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: AppSizes.sidebarWidth,
      decoration: const BoxDecoration(
        color: AppColors.sidebarBg,
        border: Border(
          right: BorderSide(color: AppColors.borderDefault, width: 1),
        ),
      ),
      child: Column(
        children: [
          // ===== Logo =====
          _buildLogo(),
          const SizedBox(height: 8),
          // ===== Nav Items =====
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = items[index];
                final isActive = activeRoute == item.route;
                return _buildNavItem(item, isActive);
              },
            ),
          ),
          // ===== Bottom User =====
          _buildBottomUser(),
        ],
      ),
    );
  }

  Widget _buildLogo() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: const BoxDecoration(
        border: Border(
          bottom: BorderSide(color: AppColors.borderDefault, width: 1),
        ),
      ),
      child: Row(
        children: [
          // Logo circle
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              gradient: AppColors.primaryGradient,
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Center(
              child: Text(
                'D',
                style: TextStyle(
                  fontFamily: 'Cairo',
                  fontSize: 16,
                  fontWeight: FontWeight.w900,
                  color: AppColors.primaryDark,
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          ShaderMask(
            shaderCallback: (bounds) =>
                AppColors.primaryGradient.createShader(bounds),
            child: const Text(
              'DT.Teeth',
              style: TextStyle(
                fontFamily: 'Cairo',
                fontSize: 16,
                fontWeight: FontWeight.w900,
                color: Colors.white,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNavItem(SidebarItem item, bool isActive) {
    return GestureDetector(
      onTap: () => onNavigate(item.route),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsets.symmetric(vertical: 2),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 9),
        decoration: BoxDecoration(
          color: isActive
              ? AppColors.accent.withOpacity(0.12)
              : Colors.transparent,
          borderRadius: BorderRadius.circular(AppSizes.radiusMD),
          border: isActive
              ? Border.all(color: AppColors.accent.withOpacity(0.25), width: 1)
              : null,
        ),
        child: Row(
          children: [
            Icon(
              item.icon,
              size: 18,
              color: isActive ? AppColors.accent : AppColors.textTertiary,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                item.label,
                style: isActive
                    ? AppTextStyles.sidebarItemActive
                    : AppTextStyles.sidebarItem,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            if (item.badge != null && item.badge! > 0)
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.error,
                  borderRadius: BorderRadius.circular(AppSizes.radiusFull),
                ),
                child: Text(
                  '${item.badge}',
                  style: const TextStyle(
                    fontFamily: 'Cairo',
                    fontSize: 9,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildBottomUser() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: const BoxDecoration(
        border: Border(
          top: BorderSide(color: AppColors.borderDefault, width: 1),
        ),
      ),
      child: Row(
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: AppColors.secondary.withOpacity(0.2),
              borderRadius: BorderRadius.circular(AppSizes.radiusFull),
              border: Border.all(
                  color: AppColors.secondary.withOpacity(0.4), width: 1),
            ),
            child: const Icon(Icons.person, size: 16, color: AppColors.secondary),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'مدير المخبر',
                  style: AppTextStyles.bodyMedium.copyWith(
                    color: AppColors.textPrimary,
                    fontWeight: FontWeight.w700,
                    fontSize: 11,
                  ),
                ),
                Text(
                  'lab@dt-teeth.com',
                  style: AppTextStyles.bodySmall.copyWith(fontSize: 9),
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          Icon(Icons.logout_rounded,
              size: 16, color: AppColors.textTertiary.withOpacity(0.6)),
        ],
      ),
    );
  }
}

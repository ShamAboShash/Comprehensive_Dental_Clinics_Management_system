import 'package:flutter/material.dart';
import '../theme/app_colors.dart';
import '../theme/app_sizes.dart';
import '../theme/app_text_styles.dart';

class AppTopbar extends StatelessWidget implements PreferredSizeWidget {
  final String title;
  final String subtitle;
  final int notificationCount;
  final VoidCallback? onNotificationTap;

  const AppTopbar({
    super.key,
    required this.title,
    this.subtitle = '',
    this.notificationCount = 0,
    this.onNotificationTap,
  });

  @override
  Size get preferredSize => const Size.fromHeight(AppSizes.topbarHeight);

  @override
  Widget build(BuildContext context) {
    return Container(
      height: AppSizes.topbarHeight,
      padding: const EdgeInsets.symmetric(horizontal: 20),
      decoration: const BoxDecoration(
        color: AppColors.backgroundDark,
        border: Border(
          bottom: BorderSide(color: AppColors.borderDefault, width: 1),
        ),
      ),
      child: Row(
        children: [
          // ===== Title =====
          Expanded(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: AppTextStyles.headlineSmall),
                if (subtitle.isNotEmpty)
                  Text(subtitle, style: AppTextStyles.bodySmall),
              ],
            ),
          ),

          // ===== Search =====
          Container(
            width: 200,
            height: 34,
            decoration: BoxDecoration(
              color: AppColors.surfaceCard,
              borderRadius: BorderRadius.circular(AppSizes.radiusMD),
              border: Border.all(color: AppColors.borderDefault),
            ),
            child: Row(
              children: [
                const SizedBox(width: 10),
                const Icon(Icons.search_rounded,
                    size: 16, color: AppColors.textTertiary),
                const SizedBox(width: 6),
                Expanded(
                  child: TextField(
                    textAlign: TextAlign.right,
                    textDirection: TextDirection.rtl,
                    decoration: InputDecoration(
                      hintText: 'بحث...',
                      hintStyle: AppTextStyles.bodySmall
                          .copyWith(color: AppColors.textMuted),
                      border: InputBorder.none,
                      isDense: true,
                      contentPadding: EdgeInsets.zero,
                    ),
                    style: AppTextStyles.bodyMedium,
                  ),
                ),
                const SizedBox(width: 8),
              ],
            ),
          ),
          const SizedBox(width: 12),

          // ===== Notification Bell =====
          Stack(
            clipBehavior: Clip.none,
            children: [
              InkWell(
                onTap: onNotificationTap,
                borderRadius: BorderRadius.circular(AppSizes.radiusMD),
                child: Container(
                  width: AppSizes.iconButtonSize,
                  height: AppSizes.iconButtonSize,
                  decoration: BoxDecoration(
                    color: AppColors.surfaceCard,
                    borderRadius: BorderRadius.circular(AppSizes.radiusMD),
                    border:
                        Border.all(color: AppColors.borderDefault, width: 1),
                  ),
                  child: const Icon(Icons.notifications_none_rounded,
                      size: 17, color: AppColors.textSecondary),
                ),
              ),
              if (notificationCount > 0)
                Positioned(
                  top: -4,
                  right: -4,
                  child: Container(
                    padding: const EdgeInsets.all(3),
                    decoration: BoxDecoration(
                      color: AppColors.error,
                      borderRadius:
                          BorderRadius.circular(AppSizes.radiusFull),
                    ),
                    child: Text(
                      '$notificationCount',
                      style: const TextStyle(
                        fontFamily: 'Cairo',
                        fontSize: 8,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(width: 10),

          // ===== Avatar =====
          Container(
            width: AppSizes.avatarSize,
            height: AppSizes.avatarSize,
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppColors.secondary, AppColors.accent],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(AppSizes.radiusFull),
            ),
            child: const Icon(Icons.person_rounded,
                size: 18, color: AppColors.primaryDark),
          ),
        ],
      ),
    );
  }
}

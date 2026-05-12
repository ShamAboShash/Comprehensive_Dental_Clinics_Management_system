import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_sizes.dart';
import '../../../../core/theme/app_text_styles.dart';

class OrderRowWidget extends StatelessWidget {
  final Map<String, dynamic> order;

  const OrderRowWidget({super.key, required this.order});

  @override
  Widget build(BuildContext context) {
    final Color statusColor = order['statusColor'] as Color;
    final bool isUrgent = order['urgent'] == true;

    return Container(
      padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
      decoration: BoxDecoration(
        color: isUrgent
            ? AppColors.error.withOpacity(0.04)
            : Colors.transparent,
        border: isUrgent
            ? const Border(
                right: BorderSide(color: AppColors.error, width: 3),
              )
            : null,
      ),
      child: Row(
        children: [
          // Order ID
          Expanded(
            flex: 2,
            child: Row(
              children: [
                if (isUrgent)
                  Padding(
                    padding: EdgeInsets.only(left: 4.w),
                    child: Icon(Icons.circle,
                        size: 7.sp, color: AppColors.error),
                  ),
                Text(
                  order['id'] ?? '',
                  style: AppTextStyles.labelMedium.copyWith(
                    color: AppColors.accent,
                    fontSize: 11.sp,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
          // Doctor
          Expanded(
            flex: 2,
            child: Text(
              order['doctor'] ?? '',
              style: AppTextStyles.bodyMedium.copyWith(fontSize: 11.sp),
              textAlign: TextAlign.center,
            ),
          ),
          // Type
          Expanded(
            flex: 2,
            child: Text(
              order['type'] ?? '',
              style: AppTextStyles.bodyMedium.copyWith(fontSize: 11.sp),
              textAlign: TextAlign.center,
            ),
          ),
          // Patient/Material
          Expanded(
            flex: 2,
            child: Text(
              order['patient'] ?? '',
              style: AppTextStyles.bodyMedium.copyWith(fontSize: 11.sp),
              textAlign: TextAlign.center,
            ),
          ),
          // Date
          Expanded(
            flex: 2,
            child: Text(
              order['date'] ?? '',
              style: AppTextStyles.bodySmall.copyWith(fontSize: 10.sp),
              textAlign: TextAlign.center,
            ),
          ),
          // Technician
          Expanded(
            flex: 2,
            child: order['technician'] != null
                ? Container(
                    margin: EdgeInsets.symmetric(horizontal: 4.w),
                    padding: EdgeInsets.symmetric(
                        horizontal: 8.w, vertical: 3.h),
                    decoration: BoxDecoration(
                      color: AppColors.secondary.withOpacity(0.12),
                      borderRadius:
                          BorderRadius.circular(AppSizes.radiusFull),
                      border: Border.all(
                          color: AppColors.secondary.withOpacity(0.3)),
                    ),
                    child: Text(
                      order['technician'],
                      style: TextStyle(
                        fontFamily: 'Cairo',
                        fontSize: 9.5.sp,
                        fontWeight: FontWeight.w700,
                        color: AppColors.secondary,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  )
                : Center(
                    child: InkWell(
                      onTap: () {},
                      child: Container(
                        padding: EdgeInsets.symmetric(
                            horizontal: 8.w, vertical: 3.h),
                        decoration: BoxDecoration(
                          color: AppColors.accent.withOpacity(0.1),
                          borderRadius:
                              BorderRadius.circular(AppSizes.radiusFull),
                          border: Border.all(
                              color: AppColors.accent.withOpacity(0.3)),
                        ),
                        child: Text(
                          '+ توكيل',
                          style: TextStyle(
                            fontFamily: 'Cairo',
                            fontSize: 9.5.sp,
                            fontWeight: FontWeight.w700,
                            color: AppColors.accent,
                          ),
                        ),
                      ),
                    ),
                  ),
          ),
          // Status Badge
          Expanded(
            flex: 2,
            child: Center(
              child: Container(
                padding: EdgeInsets.symmetric(
                    horizontal: 8.w, vertical: 3.h),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(AppSizes.radiusFull),
                  border:
                      Border.all(color: statusColor.withOpacity(0.3)),
                ),
                child: Text(
                  order['status'] ?? '',
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    fontSize: 9.5.sp,
                    fontWeight: FontWeight.w700,
                    color: statusColor,
                  ),
                ),
              ),
            ),
          ),
          // Actions
          const Expanded(
            flex: 1,
            child: Center(
              child: Icon(Icons.more_vert_rounded,
                  size: 16, color: AppColors.textTertiary),
            ),
          ),
        ],
      ),
    );
  }
}

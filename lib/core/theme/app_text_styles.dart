import 'package:flutter/material.dart';
import 'app_colors.dart';
import 'app_sizes.dart';

class AppTextStyles {
  AppTextStyles._();

  static const String _fontFamily = 'Cairo';

  // ===== Display =====
  static const TextStyle displayLarge = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontDisplay,
    fontWeight: FontWeight.w900,
    color: AppColors.textPrimary,
    letterSpacing: -0.5,
  );

  // ===== Headlines =====
  static const TextStyle headlineLarge = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontXXL,
    fontWeight: FontWeight.w800,
    color: AppColors.textPrimary,
  );

  static const TextStyle headlineMedium = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontXL,
    fontWeight: FontWeight.w700,
    color: AppColors.textPrimary,
  );

  static const TextStyle headlineSmall = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontLG,
    fontWeight: FontWeight.w700,
    color: AppColors.textPrimary,
  );

  // ===== Body =====
  static const TextStyle bodyLarge = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontMD,
    fontWeight: FontWeight.w600,
    color: AppColors.textPrimary,
  );

  static const TextStyle bodyMedium = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontSM,
    fontWeight: FontWeight.w500,
    color: AppColors.textSecondary,
  );

  static const TextStyle bodySmall = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontXS,
    fontWeight: FontWeight.w400,
    color: AppColors.textTertiary,
  );

  // ===== Labels =====
  static const TextStyle labelLarge = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontMD,
    fontWeight: FontWeight.w700,
    color: AppColors.textPrimary,
    letterSpacing: 0.3,
  );

  static const TextStyle labelMedium = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontSM,
    fontWeight: FontWeight.w700,
    color: AppColors.textSecondary,
    letterSpacing: 0.3,
  );

  static const TextStyle labelSmall = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontXS,
    fontWeight: FontWeight.w600,
    color: AppColors.textTertiary,
  );

  // ===== Special =====
  static const TextStyle statNumber = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontXXL,
    fontWeight: FontWeight.w900,
    color: AppColors.textPrimary,
  );

  static const TextStyle badgeText = TextStyle(
    fontFamily: _fontFamily,
    fontSize: 9.5,
    fontWeight: FontWeight.w700,
    color: AppColors.textPrimary,
  );

  static const TextStyle buttonText = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontMD,
    fontWeight: FontWeight.w800,
    letterSpacing: 0.5,
  );

  static const TextStyle sidebarItem = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontSM,
    fontWeight: FontWeight.w600,
    color: AppColors.textSecondary,
  );

  static const TextStyle sidebarItemActive = TextStyle(
    fontFamily: _fontFamily,
    fontSize: AppSizes.fontSM,
    fontWeight: FontWeight.w700,
    color: AppColors.accent,
  );
}

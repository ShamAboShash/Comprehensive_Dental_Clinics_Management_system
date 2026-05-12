import 'package:flutter/material.dart';

class AppColors {
  AppColors._();

  // ===== Primary Colors =====
  static const Color primary = Color(0xFF1A1C4E);
  static const Color primaryDark = Color(0xFF0F1035);
  static const Color secondary = Color(0xFFED8BFA);
  static const Color accent = Color(0xFF9EFBEC);

  // ===== Status Colors =====
  static const Color success = Color(0xFF0DBD7F);
  static const Color warning = Color(0xFFFADE1F);
  static const Color error = Color(0xFFEF4444);
  static const Color info = Color(0xFF7DD3FC);

  // ===== Background =====
  static const Color background = Color(0xFF1A1C4E);
  static const Color backgroundDark = Color(0xFF0F1035);
  static const Color surfaceCard = Color(0x0FFFFFFF);
  static const Color surfaceCardHover = Color(0x1AFFFFFF);
  static const Color sidebarBg = Color(0xFF13153E);

  // ===== Text =====
  static const Color textPrimary = Color(0xFFFFFFFF);
  static const Color textSecondary = Color(0xB3FFFFFF);
  static const Color textTertiary = Color(0x80FFFFFF);
  static const Color textMuted = Color(0x4DFFFFFF);
  static const Color textDark = Color(0xFF1A1C4E);

  // ===== Borders =====
  static const Color borderDefault = Color(0x1F9EFBEC);
  static const Color borderHover = Color(0x4D9EFBEC);
  static const Color borderActive = Color(0xFF9EFBEC);

  // ===== Systems =====
  static const Color labColor = Color(0xFFED8BFA);
  static const Color warehouseColor = Color(0xFF9EFBEC);

  // ===== Status Tags =====
  static const Color statusNew = Color(0xFF7DD3FC);
  static const Color statusManufacturing = Color(0xFFFADE1F);
  static const Color statusReady = Color(0xFF0DBD7F);
  static const Color statusDelivered = Color(0xFF9EFBEC);

  // ===== Gradients =====
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF9EFBEC), Color(0xFFED8BFA)],
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
  );

  static const LinearGradient cardGradient = LinearGradient(
    colors: [Color(0x1A9EFBEC), Color(0x0A9EFBEC)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient accentGradient = LinearGradient(
    colors: [Color(0xFF9EFBEC), Color(0xFF56CFBF)],
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
  );
}

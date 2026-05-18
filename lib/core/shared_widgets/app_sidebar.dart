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
      width: 210,
      decoration: const BoxDecoration(
        color: Color(0xFF080A24),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          // ===== Logo Block =====
          _buildLogoBlock(),

          // ===== Nav Items =====
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = items[index];
                final isActive = activeRoute == item.route;
                return _buildNavItem(item, isActive);
              },
            ),
          ),
        ],
      ),
    );
  }

  // ═══════════════════════════════════════════
  //  LOGO BLOCK — circle icon + "DT_Teeth"
  // ═══════════════════════════════════════════
  Widget _buildLogoBlock() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 28, 16, 20),
      child: Column(
        children: [
          // Outer ring
          Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: Border.all(
                color: const Color(0xFF9EFBEC).withOpacity(0.25),
                width: 1.5,
              ),
            ),
            child: Padding(
              padding: const EdgeInsets.all(4),
              // Inner filled circle
              child: Container(
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  color: Color(0xFF121535),
                ),
                child: Center(
                  child: _ToothIcon(),
                ),
              ),
            ),
          ),
          const SizedBox(height: 10),
          // "DT_Teeth" text
          ShaderMask(
            shaderCallback: (bounds) =>
                AppColors.primaryGradient.createShader(bounds),
            blendMode: BlendMode.srcIn,
            child: const Text(
              'DT_Teeth',
              style: TextStyle(
                fontFamily: 'Cairo',
                fontSize: 17,
                fontWeight: FontWeight.w900,
                color: Colors.white,
                letterSpacing: 0.5,
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ═══════════════════════════════════════════
  //  NAV ITEM
  // ═══════════════════════════════════════════
  Widget _buildNavItem(SidebarItem item, bool isActive) {
    return GestureDetector(
      onTap: () => onNavigate(item.route),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        curve: Curves.easeInOut,
        margin: const EdgeInsets.symmetric(vertical: 2),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
        decoration: BoxDecoration(
          color: isActive
              ? const Color(0xFF1C2158) // exact dark navy for active
              : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
          // RTL — icon on the right, text to its left
          textDirection: TextDirection.rtl,
          children: [
            // Icon
            Icon(
              item.icon,
              size: 19,
              color: isActive
                  ? const Color(0xFF9EFBEC)
                  : const Color(0xFFFFFFFF).withOpacity(0.65),
            ),
            const SizedBox(width: 12),
            // Label
            Expanded(
              child: Text(
                item.label,
                textDirection: TextDirection.rtl,
                style: TextStyle(
                  fontFamily: 'Cairo',
                  fontSize: 13,
                  fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
                  color: isActive
                      ? Colors.white
                      : Colors.white.withOpacity(0.65),
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ),
            // Badge (optional)
            if (item.badge != null && item.badge! > 0) ...[
              const SizedBox(width: 4),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.error,
                  borderRadius: BorderRadius.circular(20),
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
          ],
        ),
      ),
    );
  }
}

// ═══════════════════════════════════════════
//  Custom Tooth SVG-like icon (painted)
// ═══════════════════════════════════════════
class _ToothIcon extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      size: const Size(32, 36),
      painter: _ToothPainter(),
    );
  }
}

class _ToothPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0xFF9EFBEC)
      ..style = PaintingStyle.fill;

    final path = Path();
    final w = size.width;
    final h = size.height;

    // Tooth crown (top part)
    // Left bump
    path.moveTo(w * 0.12, h * 0.3);
    path.cubicTo(
      w * 0.0,  h * 0.0,
      w * 0.28, h * 0.0,
      w * 0.35, h * 0.18,
    );
    // Center dip
    path.cubicTo(
      w * 0.40, h * 0.26,
      w * 0.60, h * 0.26,
      w * 0.65, h * 0.18,
    );
    // Right bump
    path.cubicTo(
      w * 0.72, h * 0.0,
      w * 1.0,  h * 0.0,
      w * 0.88, h * 0.3,
    );
    // Right root
    path.cubicTo(
      w * 0.96, h * 0.55,
      w * 0.80, h * 0.75,
      w * 0.72, h * 1.0,
    );
    // Bottom curve between roots
    path.cubicTo(
      w * 0.65, h * 0.85,
      w * 0.55, h * 0.80,
      w * 0.50, h * 0.82,
    );
    path.cubicTo(
      w * 0.45, h * 0.80,
      w * 0.35, h * 0.85,
      w * 0.28, h * 1.0,
    );
    // Left root
    path.cubicTo(
      w * 0.20, h * 0.75,
      w * 0.04, h * 0.55,
      w * 0.12, h * 0.3,
    );

    path.close();
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

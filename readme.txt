=== WooPay - PayGate ===
Contributors: planet8co, massu0310
Donate link: http://planet8.co
Tags: woopay, planet8, woocommerce, payment gateway, woocommerce payment gateway, korea, paygate, 우페이, 플래닛에이트, 플팔, 우커머스, 결제, 한국, 페이게이트
Requires at least: 4.3
Tested up to: 4.5
Stable tag: 4.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WooCommerce Payment Gateway for PayGate

== Description ==

= English =
This is a WooCommerce payment gateway for PayGate.

You can register for a Merchant ID <a href="http://www.planet8.co/woopay-paygate-register/" target="_blank">here</a>.

Payment methods with supported currencies:

- Credit Card (issued inside South Korea): KRW, USD
- International Credit Card: USD
- Account Transfer: KRW
- Virtual Bank: KRW
- Mobile Payment: KRW
- OpenPay: KRW
- Alipay: USD, CNY
- Chinapay: USD, CNY
- Tenpay: USD, CNY

For more information, please visit http://www.planet8.co/

Need a hosting service? Try the WordPress exclusive PIUP hosting service with no PG registration fee. http://www.piup.kr/

Visit https://wordpress.org/plugins/wooshipping-postcode-kr/ to download the Korean postal code search form.

= Korean (한글) =
우커머스에서 사용 가능한 페이게이트 결제 게이트웨이 입니다.

페이게이트 PG 서비스 신청서는 <a href="http://www.planet8.co/woopay-paygate-register/" target="_blank">여기</a>를 눌러주세요.

결제 수단별 지원 통화:

- 신용카드 (국내발급): KRW, USD
- 신용카드 (해외발급): USD
- 계좌이체: KRW
- 가상계좌: KRW
- 휴대폰 소액결제: KRW
- 오픈페이: KRW
- 알리페이: USD, CNY
- 차이나페이: USD, CNY
- 텐페이: USD, CNY

더 많은 정보를 원하시면 http://www.planet8.co/ 를 방문해 주세요.

호스팅 서비스를 찾으시나요? PG 가입비가 무료인 WordPress 전용 PIUP 호스팅을 이용해 보세요! http://www.piup.kr/

우편번호 검색 플러그인은 https://wordpress.org/plugins/wooshipping-postcode-kr/ 를 방문해 주세요.

== Installation ==

= English =
1. Upload the plugin files to the `/wp-content/plugins/woopay-paygate` directory, or install the plugin through the WordPress plugins screen directly. Make sure that all the files your plugin directory has at least 755 permission if uploaded directly.

= Korean (한글) =
1. 플러그인을 `/wp-content/plugins/woopay-paygate` 디렉토리에 직접 업로드를 하시거나, WordPress 플러그인 화면에서 직접 설치를 하실 수 있습니다. 만약 직접 업로드를 하셨다면, 플러그인 디렉토리에 있는 모든 파일이 최소 755 권한을 갖고 있는지 확인 해주세요.

== Frequently Asked Questions ==

= English =
* Visit <a href="http://www.planet8.co/technical-support">http://www.planet8.co/technical-support</a> for more information

= Korean (한글) =
* <a href="http://www.planet8.co/technical-support">http://www.planet8.co/technical-support</a> 를 방문해 주세요

== Screenshots ==

= English =
1. This is the admin options screen. You can change various options here.

2. This is the payment window for PayGate.

= Korean (한글) =
1. 관리자 화면입니다. 여러가지 설정을 바꿀 수 있습니다.

2. 페이게이트 결제 화면 입니다.

== Changelog ==

= English =
= 1.1.4 =
* Bug fix for WooShipping method inactive

= 1.1.3 =
* Bug fix for settings page not showing on some nginx servers

= 1.1.2 =
* Bug fix for checking Merchant ID

= 1.1.1 =
* Bug fix for JSON call

= 1.1.0 =
* Bug fix with some point plugins
* Added e-mail support for 'Awaiting Payment'
* Removed 'Add Virtual Bank Information' option
* Added check for Virtual Bank orders for automatic cancel

= 1.0.0 =
* First version

= Korean (한글) =
= 1.1.4 =
* WooShipping 비활성화 기능 버그 수정

= 1.1.3 =
* nginx 서버 환경에서 간헐적 환경설정 창이 보이지 않던 버그 수정

= 1.1.2 =
* 머천트 아이디 버그 수정

= 1.1.1 =
* JSON 호출시 생기는 버그 수정

= 1.1.0 =
* 몇 포인트 플러그인과 생기는 충돌 수정
* '입금 대기중'을 위한 이메일 제공
* '가상계좌 정보 추가' 옵션 제거
* 입금 예정일이 지난 경우, 자동으로 주문 취소기능 적용

= 1.0.0 =
* 첫 버전
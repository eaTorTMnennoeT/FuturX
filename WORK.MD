# FuturX - 선물거래 플랫폼 개발 문서

## 1. 솔루션 개요

FuturX는 바이낸스/바이빗 스타일의 암호화폐 선물거래 플랫폼입니다.
B-Book 방식으로 운영되며, 실시간 가격 데이터는 Binance WebSocket을 통해 Node.js 서버를 경유하여 제공됩니다.

### 기술 스택
- **Backend**: PHP 8.x + PDO (MySQL)
- **Frontend**: Vanilla JS + CSS3 (반응형)
- **실시간 통신**: Node.js + Socket.IO
- **차트**: Lightweight Charts (TradingView)
- **데이터 소스**: Binance Futures API/WebSocket

---

## 2. 주요 기능

### 2.1 거래 기능 (trade.php)
- **실시간 차트**: 1분/5분/15분/1시간/4시간/1일봉 지원
- **오더북**: 매수/매도 호가창 (상위 15개)
- **최근 체결**: 실시간 체결 내역
- **주문**: 지정가/시장가 주문, 롱/숏 포지션
- **포지션 관리**: 진입가, 마크가, 청산가, 미실현 PNL 표시
- **심볼 드롭다운**:
  - 바이낸스 스타일 심볼 선택기
  - 즐겨찾기 기능 (쿠키 저장)
  - 컬럼별 정렬 (심볼/거래량/가격/변동률)
  - 3단계 정렬: 내림차순 → 오름차순 → 해제
- **레버리지**: 1x ~ 100x 조절 가능

### 2.2 지원 심볼 (12개)
| 심볼 | 소수점 | 심볼 | 소수점 |
|------|--------|------|--------|
| BTCUSDT | 2 | ETHUSDT | 2 |
| XRPUSDT | 4 | BNBUSDT | 2 |
| SOLUSDT | 3 | ADAUSDT | 5 |
| DOGEUSDT | 6 | MATICUSDT | 5 |
| DOTUSDT | 3 | LINKUSDT | 3 |
| AVAXUSDT | 3 | ZECUSDT | 2 |

### 2.3 사용자 기능
- **회원가입/로그인**: 이메일 기반 인증
- **KYC**: 신원 인증 시스템
- **지갑**: 잔고 조회, 입출금
- **보안**: 2FA, 비밀번호 변경
- **프로필**: 개인정보 관리
- **레퍼럴**: 추천인 시스템

### 2.4 대회/토너먼트
- **경쟁전**: 기간별 수익률 순위
- **토너먼트**: 이벤트성 대회
- **리더보드**: 실시간 순위표

### 2.5 관리자 기능 (/admin/)
- **대시보드**: 통계 요약
- **실시간 접속자**: Socket.IO 기반 모니터링
- **회원관리**: 계정 조회/수정
- **거래관리**: 주문/포지션 조회
- **설정**: 시스템 설정

---

## 3. 파일 구조

```
design/futures/
├── index.php              # 메인 페이지
├── trade.php              # 거래 페이지 (핵심)
├── trade.css              # 거래 페이지 스타일
├── trade_api.php          # 거래 API
├── config.php             # DB/설정
├── common.css             # 공통 스타일
│
├── login.php / register.php    # 인증
├── wallet.php / wallet-*.php   # 지갑
├── kyc.php / security.php      # 보안
├── profile.php / referral.php  # 사용자
│
├── leaderboard.php        # 리더보드
├── tournament*.php        # 토너먼트
├── competition.php        # 경쟁전
│
├── includes/              # 공통 인클루드
│   ├── bootstrap.php      # 초기화
│   ├── head.php           # HTML head
│   ├── header.php         # 헤더
│   └── footer.php         # 푸터
│
├── api/                   # API 엔드포인트
├── admin/                 # 관리자 페이지
├── sql/                   # SQL 스키마
└── ws-server/             # WebSocket 서버
    ├── server.js          # 메인 서버
    ├── start.bat          # 실행 스크립트
    └── nodemon.bat        # 개발용 실행
```

---

## 4. 개발 기록

### 2026-01-12 (오늘)

#### 4.1 심볼 드롭다운 개선
- 바이낸스 스타일 UI 적용
- USDⓈ-M 탭 기본 선택
- 즐겨찾기 쿠키 저장 (localStorage → cookie)
- 헤더 심볼 별표 동기화
- 토스트 알림 추가

#### 4.2 컬럼 정렬 기능
- 심볼/거래량/가격/변동률 정렬
- 바이낸스 스타일 화살표 (▲▼)
- 3단계 순환: 내림차순 → 오름차순 → 해제

#### 4.3 소수점 자릿수 수정
- formatPrice() 함수 심볼 파라미터 전체 적용
- 수정 위치 (14개소):
  - 차트 청산가 라인
  - 마크가격/인덱스 가격
  - 메인 가격 표시
  - 최근 거래 목록
  - 24H 통계 (High/Low/Change)
  - 주문 입력창
  - 포지션 테이블
  - 주문/거래 내역
  - P&L 기록
  - 미체결 주문 모달

---

## 5. 주의사항

### 5.1 보안 (절대 위반 금지)
- **B-Book 시스템**: 브라우저에서 Binance WebSocket 직접 연결 금지
- **데이터 흐름**: Binance → Node Server → Socket.IO → Browser
- **SQL Injection**: prepared statement 필수
- **XSS**: 모든 출력 htmlspecialchars 처리
- **CSRF**: POST 요청 토큰 검증

### 5.2 금융 데이터
- **소수점**: DECIMAL(36,18) 사용, float 금지
- **잔고**: ledger 기반, 직접 수정 금지
- **트랜잭션**: 모든 금전 로직 필수

### 5.3 WebSocket 서버
- **경로**: design/futures/ws-server/
- **포트**: 3002
- **실행**: `nodemon.bat` (개발), `start.bat` (운영)
- **재시작**: rs + Enter

### 5.4 심볼별 소수점
formatPrice() 호출 시 반드시 심볼 파라미터 전달:
```javascript
// 올바른 사용
formatPrice(price, 'XRPUSDT')  // → 2.0912 (4자리)
formatPrice(price, 'BTCUSDT')  // → 92048.00 (2자리)

// 잘못된 사용 (기본값 currentSymbol 사용됨)
formatPrice(price)  // → 주의: currentSymbol 기준
```

### 5.5 정렬 기능
- 3단계 순환: desc → asc → null (해제)
- 해제 시 원래 순서(originalOrder) 복원
- UI: 활성 방향만 노란색(#f0b90b)

---

## 6. 테스트 계정

| 구분 | Email | Password |
|------|-------|----------|
| 일반 | test@test.com | password |
| 관리자 | admin@futurx.com | password |

---

## 7. 명령어

### DB 덤프
```bash
"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe" -u root -p123123 trade > futurx_dump_YYYYMMDD.sql
```

### WebSocket 서버
```bash
cd ws-server
nodemon.bat    # 개발 (파일 변경 시 자동 재시작)
start.bat      # 운영
```

### Git
```bash
git add -A
git commit -m "[FuturX] 변경 내용"
git push origin master
```

---

## 8. 개발 시간 추정

| 기능 | 예상 시간 |
|------|-----------|
| 거래 페이지 (trade.php) | 40시간 |
| WebSocket 서버 | 16시간 |
| 사용자 인증/보안 | 12시간 |
| 지갑/입출금 | 8시간 |
| 관리자 페이지 | 16시간 |
| 대회/토너먼트 | 8시간 |
| UI/UX 디자인 | 20시간 |
| 테스트/디버깅 | 16시간 |
| **총계** | **약 136시간** |

---

## 9. TODO (향후 개발)

- [ ] 조건부 주문 (TP/SL)
- [ ] 마진 모드 (Cross/Isolated)
- [ ] 실시간 알림 (가격 알림)
- [ ] 모바일 앱 (PWA)
- [ ] API 키 발급 (외부 연동)
- [ ] 다국어 지원

---

*마지막 업데이트: 2026-01-12*

# 카본 Rust api 서버

그누보드 기반 fapi를 rust axum api로 변환한 서버입니다.
1. db를 mysql(mariadb)에서 postgresql로 변환
2. 도메인 단위로 마이크로 서비스로 분리
3. 각 로직을 php에서 rust axum으로 변환
4. pii 민감정보 분리
5. 프론트엔드는 그대로 사용

고가용성의 마이크로서비스로 분리해서 엔터프라이즈급 서비스로 순차적으로 전환할 수 있도록
기존 php fapi를 구성과 구조 그리고 query를 rust axum api에 최적화 하여 설계되어 있는 것을 ai와 협업하여 서비스 업그레이드

프론트엔드는 그대로 사용하면서 백엔드와 sql만 이전해서 엔터프라이즈급 성능에 대응한다. 
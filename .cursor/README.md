# Cursor MCP Browser 설정 가이드

## 브라우저 MCP 활성화 방법

### 방법 1: Cursor UI에서 설정
1. Cursor 열기 → `Ctrl+,` (설정)
2. 검색창에 `MCP` 입력
3. **MCP Servers** 섹션 확인
4. `cursor-ide-browser` 또는 `cursor-browser-extension` 활성화

### 방법 2: Command Palette 사용
1. `Ctrl+Shift+P` (Command Palette)
2. `MCP: Configure Servers` 또는 `MCP: Restart` 실행
3. 설정 파일이 열리면 브라우저 MCP 서버 추가

### 방법 3: 수동 설정 파일 위치
- **Windows**: `%APPDATA%\Cursor\User\globalStorage\cursor.mcp\settings.json`
- **Mac**: `~/Library/Application Support/Cursor/User/globalStorage/cursor.mcp/settings.json`

### 설정 파일 예시
```json
{
  "mcpServers": {
    "cursor-ide-browser": {
      "enabled": true
    },
    "cursor-browser-extension": {
      "enabled": true
    }
  }
}
```

### 확인 방법
1. Cursor 재시작
2. 이 채팅에서 AI가 브라우저 관련 툴을 사용할 수 있는지 확인
3. Command Palette에서 `MCP: Status` 확인

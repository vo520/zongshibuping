# FoxCMS API Postman 使用指南

> 本文档帮助你快速导入和使用 FoxCMS API 接口集合

## 📦 文件说明

```
app/api/postman/
├── FoxCMS_API_v1.0.postman_collection.json     # API 集合
├── FoxCMS_API_Environment.postman_environment.json # 环境配置
└── test-scripts.js                           # 测试脚本
```

## 🚀 快速开始

### 1. 导入集合

1. 打开 Postman
2. 点击 **Import** 按钮
3. 选择 `FoxCMS_API_v1.0.postman_collection.json` 文件
4. 点击 **Import** 完成导入

### 2. 导入环境

1. 点击右上角 **Settings** (齿轮图标)
2. 选择 **Environments**
3. 点击 **Import**
4. 选择 `FoxCMS_API_Environment.postman_environment.json`
5. 配置环境变量

### 3. 选择环境

1. 右上角环境选择器
2. 选择 **FoxCMS API 环境配置**
3. 修改 `baseUrl` 为你的 API 地址

## ⚙️ 环境配置

### 修改服务器地址

在环境中设置：

| 变量 | 说明 | 示例 |
|------|------|------|
| `baseUrl` | API 服务器地址 | `http://localhost` |
| `apiPath` | API 路径前缀 | `/api` |
| `lang` | 默认语言 | `zh` |

### 生产环境配置

启用生产环境变量：

```json
{
  "key": "baseUrl",
  "value": "https://api.your-domain.com",
  "enabled": true
}
```

## 📚 API 端点列表

### 栏目 API
| 方法 | 端点 | 说明 |
|------|------|------|
| GET | `/column/list` | 栏目列表 |
| GET | `/column/detail` | 栏目详情 |
| GET | `/column/tree` | 栏目树 |
| GET | `/column/breadcrumb` | 面包屑 |

### 文章 API
| 方法 | 端点 | 说明 |
|------|------|------|
| GET | `/article/list` | 文章列表 |
| GET | `/article/detail` | 文章详情 |
| GET | `/article/recommend` | 推荐文章 |
| GET | `/article/hot` | 热门文章 |

### 产品 API
| 方法 | 端点 | 说明 |
|------|------|------|
| GET | `/product/list` | 产品列表 |
| GET | `/product/detail` | 产品详情 |
| GET | `/product/recommend` | 推荐产品 |

### 图片/视频/下载 API
| 方法 | 端点 | 说明 |
|------|------|------|
| GET | `/images/list` | 图片列表 |
| GET | `/images/detail` | 图片详情 |
| GET | `/video/list` | 视频列表 |
| GET | `/video/detail` | 视频详情 |
| GET | `/download/list` | 下载列表 |
| GET | `/download/detail` | 下载详情 |

### 其他内容
| 方法 | 端点 | 说明 |
|------|------|------|
| GET | `/other/single` | 单页内容 |
| GET | `/other/slides` | 幻灯片 |
| GET | `/other/links` | 友情链接 |
| GET | `/other/tags` | 标签列表 |
| GET | `/other/nav` | 导航菜单 |
| GET | `/other/config` | 系统配置 |

### 标签内容
| 方法 | 端点 | 说明 |
|------|------|------|
| GET | `/tag/article` | 标签文章 |
| GET | `/tag/product` | 标签产品 |
| GET | `/tag/hot` | 热门标签 |

### 统计 API
| 方法 | 端点 | 说明 |
|------|------|------|
| GET | `/stats/overview` | 统计概览 |
| GET | `/stats/popular` | 热门内容 |
| GET | `/stats/visits` | 访问统计 |
| GET | `/stats/columns` | 栏目统计 |

### 反馈 API
| 方法 | 端点 | 说明 |
|------|------|------|
| GET | `/feedback/list` | 反馈列表 |
| GET | `/feedback/detail` | 反馈详情 |
| GET | `/feedback/statistics` | 反馈统计 |

### 搜索
| 方法 | 端点 | 说明 |
|------|------|------|
| GET | `/search` | 全局搜索 |

## 🧪 测试用例

### 使用预置测试

在集合的 **测试用例** 文件夹中包含以下测试：

1. **参数验证 - 无效ID** - 测试无效参数处理
2. **资源不存在** - 测试 404 错误处理
3. **分页边界测试** - 测试分页功能
4. **CORS 预检请求** - 测试跨域支持

### 运行测试

1. 选择集合或文件夹
2. 点击 **Run Collection**
3. 配置迭代次数和环境
4. 点击 **Run** 开始测试

### 查看测试结果

测试完成后显示：
- ✅ 通过的测试数
- ❌ 失败的测试数
- 📊 响应时间统计

## 📝 请求示例

### 带参数请求

```http
GET {{baseUrl}}{{apiPath}}/article/list?lang={{lang}}&page=1&pageSize=10
```

### 带认证请求

添加请求头：

```http
X-App-Id: app_001
X-Timestamp: 1704067200
X-Signature: md5(appId + timestamp + params + secret)
```

## 🔍 响应格式

### 成功响应

```json
{
  "code": 1,
  "msg": "success",
  "data": { ... },
  "time": 1704067200,
  "requestId": "req_xxx"
}
```

### 分页响应

```json
{
  "code": 1,
  "data": {
    "list": [...],
    "pagination": {
      "page": 1,
      "pageSize": 15,
      "total": 100,
      "totalPages": 7,
      "hasMore": true
    }
  }
}
```

### 错误响应

```json
{
  "code": 1001,
  "msg": "Invalid parameter",
  "data": null
}
```

## 🛡️ 限流说明

| 接口类型 | 限制 | 说明 |
|---------|------|------|
| 默认 | 60次/分钟 | 标准接口 |
| 搜索 | 30次/分钟 | 搜索接口 |
| 详情 | 100次/分钟 | 单个资源详情 |

响应头包含限流信息：
- `X-RateLimit-Limit` - 最大请求数
- `X-RateLimit-Remaining` - 剩余请求数
- `X-RateLimit-Reset` - 重置时间戳

## 🔒 签名认证

### 生成签名

```javascript
const crypto = require('crypto-js');

function generateSignature(appId, timestamp, params, secret) {
  const sortedParams = Object.keys(params)
    .sort()
    .map(k => `${k}=${params[k]}`)
    .join('&');

  const signString = appId + timestamp + sortedParams + secret;
  return crypto.MD5(signString).toString().toLowerCase();
}
```

### 请求头示例

```
X-App-Id: app_001
X-Timestamp: 1704067200
X-Signature: a1b2c3d4e5f6...
```

## 📊 状态码

| 状态码 | 说明 |
|--------|------|
| 1 | 成功 |
| 0 | 失败 |
| 1001 | 参数错误 |
| 1005 | 资源未找到 |
| 1008 | 请求频率超限 |
| 2001 | 服务器错误 |

## 🐛 常见问题

### Q: 导入失败？

检查 JSON 格式是否正确，确保文件未被损坏。

### Q: 请求超时？

1. 检查 `baseUrl` 是否正确
2. 确认服务器是否运行
3. 增加超时时间设置

### Q: CORS 错误？

API 已配置 CORS，确保请求来自允许的域名。

## 📞 技术支持

- 📧 邮箱：support@your-domain.com
- 📖 文档：https://docs.your-domain.com

---

**版本**: v1.0.0
**更新日期**: 2024-01-01
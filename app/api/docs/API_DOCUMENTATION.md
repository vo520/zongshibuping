# FoxCMS API 接入文档

> **版本**: v1.0.0
> **更新日期**: 2024-01-01
> **文档状态**: ✅ 正式发布

---

## 📑 目录

1. [快速开始](#快速开始)
2. [基础信息](#基础信息)
3. [认证方式](#认证方式)
4. [接口列表](#接口列表)
5. [错误码对照表](#错误码对照表)
6. [SDK 使用指南](#sdk-使用指南)
7. [常见问题](#常见问题)
8. [性能优化](#性能优化)
9. [安全注意事项](#安全注意事项)

---

## 🚀 快速开始

### 安装依赖

```bash
# 使用 npm
npm install axios

# 使用 yarn
yarn add axios
```

### 快速示例

```typescript
import axios from 'axios';

// 创建 API 客户端
const api = axios.create({
  baseURL: 'https://your-domain.com/api',
  timeout: 10000,
});

// 获取文章列表
async function getArticles() {
  const response = await api.get('/article/list', {
    params: { lang: 'zh', page: 1, pageSize: 10 }
  });
  console.log(response.data);
  // { code: 1, msg: 'success', data: { list: [...], pagination: {...} } }
}
```

---

## 📌 基础信息

### API 地址

| 环境 | 地址 | 说明 |
|------|------|------|
| 生产环境 | `https://api.your-domain.com` | 正式服务 |
| 测试环境 | `https://test-api.your-domain.com` | 测试服务 |

### 版本说明

- **当前版本**: v1
- **版本控制**: 通过 URL 路径 `/api/` 前缀
- **向下兼容**: v1 版本保持向后兼容

### 数据格式

- **请求格式**: `application/x-www-form-urlencoded` 或 `application/json`
- **响应格式**: `application/json`

---

## 🔐 认证方式

### 公开接口（无需认证）

以下接口无需任何认证即可访问：

```
GET /api/column/list
GET /api/column/tree
GET /api/article/list
GET /api/article/recommend
GET /api/article/hot
GET /api/product/list
GET /api/product/recommend
GET /api/images/list
GET /api/video/list
GET /api/download/list
GET /api/other/slides
GET /api/other/links
GET /api/other/tags
GET /api/other/nav
GET /api/other/config
```

### 签名认证（可选）

对于需要认证的接口，请在请求头中添加以下参数：

| 参数 | 说明 | 示例 |
|------|------|------|
| `X-App-Id` | 应用 ID | `app_001` |
| `X-Timestamp` | 时间戳（秒） | `1704067200` |
| `X-Signature` | 签名 | `a1b2c3d4e5f6...` |

**签名算法**:

```javascript
const crypto = require('crypto');

function generateSignature(appId, timestamp, params, secret) {
  // 1. 参数按 key 排序
  const sortedParams = Object.keys(params)
    .sort()
    .map(key => `${key}=${params[key]}`)
    .join('&');

  // 2. 拼接签名串
  const signString = appId + timestamp + sortedParams + secret;

  // 3. 计算 MD5
  return crypto.createHash('md5').update(signString).digest('hex').toLowerCase();
}
```

---

## 📚 接口列表

### 1. 栏目 API

#### 1.1 获取栏目列表

```
GET /api/column/list
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 | 示例 |
|------|------|------|------|------|
| lang | string | 否 | 语言标识 | `zh`、`en` |
| pid | int | 否 | 父级栏目 ID，-1 表示全部 | `0` |

**响应示例**:

```json
{
  "code": 1,
  "msg": "success",
  "data": [
    {
      "id": 1,
      "pid": 0,
      "name": "新闻",
      "model": "article",
      "type": 1,
      "seoTitle": "新闻频道",
      "seoKeyword": "新闻,资讯",
      "seoDesc": "新闻频道描述",
      "urlname": "news",
      "picUrl": "/uploads/column.jpg"
    }
  ],
  "time": 1704067200,
  "requestId": "req_abc123"
}
```

#### 1.2 获取栏目详情

```
GET /api/column/detail
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✅ | 栏目 ID |

**响应示例**:

```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "id": 1,
    "pid": 0,
    "name": "新闻",
    "model": "article",
    "content": "<p>栏目详细内容...</p>",
    "seoTitle": "新闻频道",
    "lang": "zh"
  }
}
```

#### 1.3 获取栏目树

```
GET /api/column/tree
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| lang | string | 否 | 语言标识 |

**响应示例**:

```json
{
  "code": 1,
  "msg": "success",
  "data": [
    {
      "id": 1,
      "pid": 0,
      "name": "新闻",
      "model": "article",
      "urlname": "news",
      "children": [
        {
          "id": 2,
          "pid": 1,
          "name": "公司新闻",
          "model": "article",
          "urlname": "company-news",
          "children": []
        }
      ]
    }
  ]
}
```

#### 1.4 获取面包屑

```
GET /api/column/breadcrumb
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✅ | 栏目 ID |

**响应示例**:

```json
{
  "code": 1,
  "msg": "success",
  "data": [
    { "id": 1, "name": "首页", "urlname": "index" },
    { "id": 3, "name": "产品中心", "urlname": "product" },
    { "id": 5, "name": "电子产品", "urlname": "electronic" }
  ]
}
```

---

### 2. 文章 API

#### 2.1 获取文章列表

```
GET /api/article/list
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 | 示例 |
|------|------|------|------|------|
| page | int | 否 | 页码，默认 `1` | `1` |
| pageSize | int | 否 | 每页数量，默认 `15`，最大 `100` | `10` |
| lang | string | 否 | 语言标识 | `zh` |
| columnId | int | 否 | 栏目 ID，支持子栏目 | `5` |
| tag | string | 否 | 标签筛选 | `PHP` |
| recommend | string | 否 | 推荐筛选，传入 `1` | `1` |

**响应示例**:

```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "list": [
      {
        "id": 1,
        "columnId": 5,
        "title": "文章标题",
        "subtitle": "文章副标题",
        "description": "文章描述...",
        "author": "admin",
        "source": "原创",
        "click": 100,
        "releaseTime": "2024-01-01 10:00:00",
        "imgUrl": "/uploads/article.jpg",
        "tags": ["PHP", "ThinkPHP"],
        "isRecommend": true,
        "isTop": false,
        "isHot": false
      }
    ],
    "pagination": {
      "page": 1,
      "pageSize": 10,
      "total": 100,
      "totalPages": 10,
      "hasMore": true
    }
  }
}
```

#### 2.2 获取文章详情

```
GET /api/article/detail
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✅ | 文章 ID |

**响应示例**:

```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "id": 1,
    "columnId": 5,
    "columnName": "新闻",
    "title": "文章标题",
    "subtitle": "副标题",
    "description": "文章描述",
    "content": "<p>文章正文...</p>",
    "author": "admin",
    "source": "原创",
    "click": 101,
    "releaseTime": "2024-01-01 10:00:00",
    "imgUrl": "/uploads/article.jpg",
    "tags": ["PHP"],
    "articleField": "c,t"
  }
}
```

#### 2.3 获取推荐文章

```
GET /api/article/recommend
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| lang | string | 否 | 语言标识 |
| columnId | int | 否 | 栏目 ID |
| limit | int | 否 | 返回数量，默认 `5`，最大 `20` |

#### 2.4 获取热门文章

```
GET /api/article/hot
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| lang | string | 否 | 语言标识 |
| columnId | int | 否 | 栏目 ID |
| limit | int | 否 | 返回数量，默认 `10`，最大 `20` |

---

### 3. 产品 API

#### 3.1 获取产品列表

```
GET /api/product/list
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码 |
| pageSize | int | 否 | 每页数量 |
| lang | string | 否 | 语言标识 |
| columnId | int | 否 | 栏目 ID |
| recommend | string | 否 | 推荐筛选 |

#### 3.2 获取产品详情

```
GET /api/product/detail
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✅ | 产品 ID |

**响应示例**:

```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "id": 1,
    "columnId": 3,
    "columnName": "电子产品",
    "title": "产品名称",
    "subtitle": "产品副标题",
    "description": "产品描述",
    "content": "<p>产品详情...</p>",
    "price": "1999.00",
    "marketPrice": "2499.00",
    "productNo": "P001",
    "stock": 100,
    "click": 50,
    "releaseTime": "2024-01-01",
    "imgUrl": "/uploads/product.jpg",
    "images": [
      { "url": "/uploads/product1.jpg" },
      { "url": "/uploads/product2.jpg" }
    ],
    "picSet": [],
    "tags": ["热门", "新品"],
    "params": []
  }
}
```

#### 3.3 获取推荐产品

```
GET /api/product/recommend
```

---

### 4. 图片/视频/下载 API

#### 4.1 图片列表

```
GET /api/images/list
```

#### 4.2 图片详情

```
GET /api/images/detail
```

#### 4.3 视频列表

```
GET /api/video/list
```

#### 4.4 视频详情

```
GET /api/video/detail
```

#### 4.5 下载列表

```
GET /api/download/list
```

#### 4.6 下载详情

```
GET /api/download/detail
```

---

### 5. 其他内容 API

#### 5.1 获取单页内容

```
GET /api/other/single
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| columnId | int | ✅ | 栏目 ID |

#### 5.2 获取友情链接

```
GET /api/other/links
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 否 | 类型：`all`、`logo`、`text` |
| limit | int | 否 | 返回数量 |

#### 5.3 获取幻灯片

```
GET /api/other/slides
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| group | string | 否 | 分组，默认 `default` |
| limit | int | 否 | 返回数量 |

#### 5.4 获取标签列表

```
GET /api/other/tags
```

#### 5.5 获取导航菜单

```
GET /api/other/nav
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| position | string | 否 | 位置：`header`、`footer` |
| lang | string | 否 | 语言标识 |

#### 5.6 获取系统配置

```
GET /api/other/config
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| key | string | 否 | 配置项名称，不传则返回全部 |

---

### 6. 标签内容 API

#### 6.1 获取标签下的文章

```
GET /api/tag/article
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| tag | string | ✅ | 标签名称 |
| page | int | 否 | 页码 |
| pageSize | int | 否 | 每页数量 |
| lang | string | 否 | 语言标识 |

#### 6.2 获取标签下的产品

```
GET /api/tag/product
```

#### 6.3 获取热门标签

```
GET /api/tag/hot
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| limit | int | 否 | 返回数量 |
| lang | string | 否 | 语言标识 |

---

### 7. 统计 API

#### 7.1 获取统计概览

```
GET /api/stats/overview
```

**响应示例**:

```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "article": 100,
    "product": 50,
    "images": 30,
    "video": 20,
    "download": 15,
    "column": 10,
    "total": 225
  }
}
```

#### 7.2 获取热门内容

```
GET /api/stats/popular
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 否 | 内容类型：`article`、`product`、`images` 等 |
| limit | int | 否 | 返回数量 |
| lang | string | 否 | 语言标识 |

#### 7.3 获取访问统计

```
GET /api/stats/visits
```

#### 7.4 获取栏目统计

```
GET /api/stats/columns
```

---

### 8. 反馈 API

#### 8.1 获取反馈列表

```
GET /api/feedback/list
```

#### 8.2 获取反馈详情

```
GET /api/feedback/detail
```

#### 8.3 获取反馈统计

```
GET /api/feedback/statistics
```

---

### 9. 搜索 API

```
GET /api/search
```

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| keyword | string | ✅ | 搜索关键词 |
| type | string | 否 | 搜索类型：`all`、`article`、`product` 等 |
| lang | string | 否 | 语言标识 |
| page | int | 否 | 页码 |
| pageSize | int | 否 | 每页数量 |

**响应示例**:

```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "article": {
      "list": [...],
      "pagination": {...}
    },
    "product": {
      "list": [...],
      "pagination": {...}
    }
  }
}
```

---

## ❌ 错误码对照表

| 状态码 | 说明 | 处理建议 |
|--------|------|----------|
| `1` | 成功 | - |
| `0` | 失败 | 检查请求参数 |
| `1001` | 参数错误 | 检查传入参数格式 |
| `1002` | 缺少必需参数 | 检查必填参数 |
| `1003` | 未认证 | 登录或添加认证信息 |
| `1004` | 禁止访问 | 检查权限 |
| `1005` | 资源未找到 | 检查 ID 是否正确 |
| `1006` | 方法不允许 | 检查 HTTP 方法 |
| `1007` | 请求超时 | 重试或增加超时时间 |
| `1008` | 请求频率超限 | 降低请求频率 |
| `1009` | 无效签名 | 检查签名算法 |
| `1010` | Token 过期 | 刷新 Token |
| `1011` | 无效 Token | 重新登录 |
| `2001` | 服务器内部错误 | 联系技术支持 |
| `2002` | 数据库错误 | 稍后重试 |
| `2003` | 缓存错误 | 稍后重试 |

---

## 🛠 SDK 使用指南

### React Hooks 封装

```typescript
// hooks/useApi.ts
import { useState, useEffect, useCallback } from 'react';
import axios, { AxiosInstance } from 'axios';

const api: AxiosInstance = axios.create({
  baseURL: process.env.REACT_APP_API_URL,
  timeout: 10000,
});

// 请求拦截器
api.interceptors.request.use(
  (config) => {
    // 添加认证信息
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// 响应拦截器
api.interceptors.response.use(
  (response) => {
    if (response.data.code !== 1) {
      console.error('API Error:', response.data.msg);
    }
    return response.data;
  },
  (error) => {
    console.error('Request Error:', error);
    return Promise.reject(error);
  }
);

export const useApi = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const request = useCallback(async (
    method: 'get' | 'post',
    url: string,
    params?: Record<string, any>
  ) => {
    setLoading(true);
    setError(null);

    try {
      const response = method === 'get'
        ? await api.get(url, { params })
        : await api.post(url, params);

      return response.data;
    } catch (err: any) {
      const message = err.response?.data?.msg || '请求失败';
      setError(message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  return { request, loading, error };
};

// 使用示例
export function useArticleList(params = {}) {
  const { request, loading, error } = useApi();
  const [data, setData] = useState<any[]>([]);

  const fetchData = useCallback(async () => {
    try {
      const result = await request('get', '/article/list', params);
      setData(result.data.list);
    } catch (e) {
      // 错误已在 useApi 中处理
    }
  }, [request, JSON.stringify(params)]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  return { data, loading, error, refetch: fetchData };
}
```

### Vue Composable 封装

```typescript
// composables/useApi.ts
import { ref, readonly } from 'vue';
import axios, { AxiosInstance } from 'axios';

const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  timeout: 10000,
});

export function useApi() {
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function request<T = any>(
    method: 'get' | 'post',
    url: string,
    params?: Record<string, any>
  ): Promise<T> {
    loading.value = true;
    error.value = null;

    try {
      const response = method === 'get'
        ? await api.get(url, { params })
        : await api.post(url, params);

      return response.data as T;
    } catch (err: any) {
      const message = err.response?.data?.msg || '请求失败';
      error.value = message;
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    request,
    loading: readonly(loading),
    error: readonly(error)
  };
}

// 文章相关 API
export function useArticle() {
  const { request, loading, error } = useApi();

  async function getList(params = {}) {
    return request('get', '/article/list', params);
  }

  async function getDetail(id: number) {
    return request('get', '/article/detail', { id });
  }

  async function getRecommend(params = {}) {
    return request('get', '/article/recommend', params);
  }

  async function getHot(params = {}) {
    return request('get', '/article/hot', params);
  }

  return {
    getList,
    getDetail,
    getRecommend,
    getHot,
    loading,
    error
  };
}
```

### 类组件 HOC 封装

```typescript
// withApi.tsx
import React from 'react';

export function withApi<P extends object>(
  WrappedComponent: React.ComponentType<P>,
  apiMethod: (params?: any) => Promise<any>
) {
  return function WithApiComponent(props: Omit<P, 'data' | 'loading' | 'error'>) {
    const [data, setData] = React.useState<any>(null);
    const [loading, setLoading] = React.useState(false);
    const [error, setError] = React.useState<string | null>(null);

    const refetch = React.useCallback(async (params?: any) => {
      setLoading(true);
      setError(null);

      try {
        const result = await apiMethod(params);
        setData(result.data);
      } catch (e: any) {
        setError(e.message);
      } finally {
        setLoading(false);
      }
    }, [apiMethod]);

    React.useEffect(() => {
      refetch();
    }, []);

    return (
      <WrappedComponent
        {...props as P}
        data={data}
        loading={loading}
        error={error}
        refetch={refetch}
      />
    );
  };
}

// 使用示例
const ArticleListWithApi = withApi(
  ({ data, loading, error, refetch }) => (
    <div>
      {loading && <Loading />}
      {error && <ErrorMessage error={error} onRetry={refetch} />}
      {data?.map(item => <ArticleItem key={item.id} {...item} />)}
    </div>
  ),
  (params) => axios.get('/api/article/list', { params })
);
```

---

## ❓ 常见问题

### Q1: 如何处理跨域问题？

API 已配置 CORS 头部，公开接口支持跨域请求。对于需要认证的接口，请确保请求头中包含正确的签名信息。

### Q2: 如何处理请求超时？

```typescript
// 设置全局超时
axios.defaults.timeout = 10000;

// 单个请求超时
api.get('/article/list', {
  timeout: 5000
});
```

### Q3: 如何缓存 API 响应？

```typescript
// 使用 SWR 或 React Query
import useSWR from 'swr';

function ArticleList() {
  const { data, error } = useSWR(
    '/api/article/list',
    (url) => fetch(url).then(res => res.json()),
    { revalidateOnFocus: false }
  );
}
```

### Q4: 如何处理 401/403 错误？

```typescript
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // 跳转登录
      window.location.href = '/login';
    }
    if (error.response?.status === 403) {
      // 显示无权限提示
      alert('您没有权限访问此资源');
    }
    return Promise.reject(error);
  }
);
```

### Q5: 如何实现自动重试？

```typescript
import axios-retry from 'axios-retry';

axiosRetry(api, {
  retries: 3,
  retryDelay: (retryCount) => retryCount * 1000,
  retryCondition: (error) => {
    return error.response?.status === 503 || error.code === 'ECONNABORTED';
  }
});
```

---

## ⚡ 性能优化

### 1. 请求优化

- **合并请求**: 使用 `/api/search` 一次性获取多种类型数据
- **分页加载**: 使用合理的 `pageSize`，避免一次加载过多数据
- **缓存策略**: 对不常变化的数据启用本地缓存

### 2. 响应优化

- **字段筛选**: 只请求需要的字段
- **懒加载**: 对列表数据使用分页或懒加载
- **CDN 加速**: 确保图片等静态资源使用 CDN

### 3. 限流应对

| 端点类型 | 限制 | 建议 |
|----------|------|------|
| 默认 | 60次/分钟 | 合理控制请求频率 |
| 搜索 | 30次/分钟 | 添加防抖处理 |
| 详情 | 100次/分钟 | 对详情页启用缓存 |

### 4. 推荐缓存时间

| 数据类型 | 缓存时间 | 说明 |
|----------|----------|------|
| 列表数据 | 5 分钟 | 文章列表、产品列表 |
| 详情数据 | 10 分钟 | 文章详情、产品详情 |
| 配置信息 | 10 分钟 | 系统配置、导航菜单 |
| 统计数据 | 1 分钟 | 访问统计 |

---

## 🔒 安全注意事项

### 1. 敏感信息保护

- ⚠️ 不要在前端代码中硬编码 API 密钥
- ⚠️ 使用环境变量存储敏感配置
- ⚠️ 生产环境务必启用 HTTPS

### 2. XSS 防护

- 对用户输入进行 HTML 转义
- 使用 React/Vue 的默认转义机制
- 避免使用 `dangerouslySetInnerHTML`

### 3. CSRF 防护

- 使用 SameSite Cookie
- 添加 CSRF Token 验证

### 4. 请求签名

```typescript
// 生成签名请求
function createSignedRequest(params: Record<string, string>) {
  const timestamp = Math.floor(Date.now() / 1000);
  const appId = process.env.API_APP_ID;
  const secret = process.env.API_SECRET;

  const signature = generateSignature(appId, timestamp, params, secret);

  return {
    ...params,
    _timestamp: timestamp,
    _appId: appId,
    _signature: signature
  };
}
```

---

## 📊 响应头信息

API 响应会包含以下限流相关头信息：

| 头信息 | 说明 |
|--------|------|
| `X-RateLimit-Limit` | 时间窗口内允许的最大请求数 |
| `X-RateLimit-Remaining` | 剩余可用请求数 |
| `X-RateLimit-Reset` | 限流重置时间戳 |

---

## 📝 更新日志

| 版本 | 日期 | 说明 |
|------|------|------|
| v1.0.0 | 2024-01-01 | 初始版本发布 |

---

## 📞 技术支持

- **邮箱**: support@your-domain.com
- **文档地址**: https://docs.your-domain.com

---

*本文档最后更新于 2024-01-01*
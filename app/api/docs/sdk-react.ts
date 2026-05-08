/**
 * FoxCMS API React Hooks SDK
 *
 * @version 1.0.0
 * @description React Hooks 封装，让 API 调用更简单
 */

import { useState, useEffect, useCallback, useMemo } from 'react';
import axios, { AxiosInstance, AxiosRequestConfig, AxiosError } from 'axios';
import type {
  ApiResponse,
  PaginatedResponse,
  Pagination,
  Article,
  Column,
  Product,
  Images,
  Video,
  Download,
  Tag,
  Link,
  Slide,
  Nav,
  ColumnTree,
  Breadcrumb,
  StatsOverview,
  PopularItem,
  Feedback,
  FeedbackStats,
  SearchResult,
  ApiCode,
  ApiError,
  PageParams,
  ListParams,
  DetailParams,
  SearchParams
} from './types';

// ==================== API 客户端配置 ====================

/**
 * API 配置选项
 */
interface ApiOptions {
  baseURL: string;
  timeout?: number;
  getAuthToken?: () => string | null;
  onAuthError?: () => void;
  onRateLimit?: () => void;
}

/**
 * 创建 API 客户端
 */
export function createApiClient(options: ApiOptions): AxiosInstance {
  const api = axios.create({
    baseURL: options.baseURL,
    timeout: options.timeout || 10000,
    headers: {
      'Content-Type': 'application/json'
    }
  });

  // 请求拦截器
  api.interceptors.request.use(
    (config) => {
      const token = options.getAuthToken?.();
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }

      // 添加请求 ID
      config.headers['X-Request-Id'] = `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

      return config;
    },
    (error) => Promise.reject(error)
  );

  // 响应拦截器
  api.interceptors.response.use(
    (response) => {
      const data = response.data;

      // 检查限流
      if (data.code === 1008) {
        options.onRateLimit?.();
      }

      return data;
    },
    (error: AxiosError) => {
      if (error.response?.status === 401) {
        options.onAuthError?.();
      }
      return Promise.reject(error);
    }
  );

  return api;
}

// ==================== Hook 基础封装 ====================

/**
 * API 请求 Hook 结果
 */
interface UseRequestResult<T> {
  data: T | null;
  loading: boolean;
  error: string | null;
  refetch: () => Promise<void>;
}

/**
 * 分页 Hook 结果
 */
interface UseListResult<T> extends UseRequestResult<T[]> {
  pagination: Pagination | null;
  loadMore: () => Promise<void>;
  refresh: () => Promise<void>;
}

// ==================== 栏目 API Hooks ====================

/**
 * 获取栏目列表
 */
export function useColumnList(params: { lang?: string; pid?: number } = {}) {
  const [data, setData] = useState<Column[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const api = getApiClient();
      const response = await api.get<ApiResponse<Column[]>>('/column/list', { params });
      if (response.code === 1) {
        setData(response.data);
      } else {
        setError(response.msg);
      }
    } catch (e: any) {
      setError(e.message || '请求失败');
    } finally {
      setLoading(false);
    }
  }, [JSON.stringify(params)]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  return { data, loading, error, refetch: fetchData };
}

/**
 * 获取栏目树
 */
export function useColumnTree(lang?: string) {
  const [data, setData] = useState<ColumnTree[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      try {
        const api = getApiClient();
        const response = await api.get<ApiResponse<ColumnTree[]>>('/column/tree', {
          params: { lang }
        });
        if (response.code === 1) {
          setData(response.data);
        } else {
          setError(response.msg);
        }
      } catch (e: any) {
        setError(e.message);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [lang]);

  return { data, loading, error };
}

/**
 * 获取栏目详情
 */
export function useColumnDetail(id: number) {
  const [data, setData] = useState<Column | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!id) return;

    const fetchData = async () => {
      setLoading(true);
      try {
        const api = getApiClient();
        const response = await api.get<ApiResponse<Column>>('/column/detail', {
          params: { id }
        });
        if (response.code === 1) {
          setData(response.data);
        } else {
          setError(response.msg);
        }
      } catch (e: any) {
        setError(e.message);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [id]);

  return { data, loading, error };
}

/**
 * 获取面包屑
 */
export function useBreadcrumb(id: number) {
  const [data, setData] = useState<Breadcrumb[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!id) return;

    const fetchData = async () => {
      setLoading(true);
      try {
        const api = getApiClient();
        const response = await api.get<ApiResponse<Breadcrumb[]>>('/column/breadcrumb', {
          params: { id }
        });
        if (response.code === 1) {
          setData(response.data);
        } else {
          setError(response.msg);
        }
      } catch (e: any) {
        setError(e.message);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [id]);

  return { data, loading, error };
}

// ==================== 文章 API Hooks ====================

/**
 * 获取文章列表
 */
export function useArticleList(params: ListParams & { tag?: string } = {}) {
  const [data, setData] = useState<Article[]>([]);
  const [pagination, setPagination] = useState<Pagination | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const api = getApiClient();
      const response = await api.get<PaginatedResponse<Article>>('/article/list', {
        params: { page: 1, pageSize: 15, ...params }
      });
      if (response.code === 1) {
        setData(response.data.list);
        setPagination(response.data.pagination);
      } else {
        setError(response.msg);
      }
    } catch (e: any) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }, [JSON.stringify(params)]);

  const loadMore = useCallback(async () => {
    if (!pagination?.hasMore || loading) return;

    try {
      const api = getApiClient();
      const response = await api.get<PaginatedResponse<Article>>('/article/list', {
        params: { page: pagination.page + 1, pageSize: pagination.pageSize, ...params }
      });
      if (response.code === 1) {
        setData([...data, ...response.data.list]);
        setPagination(response.data.pagination);
      }
    } catch (e) {
      console.error(e);
    }
  }, [pagination, loading, data, params]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  return { data, pagination, loading, error, loadMore, refresh: fetchData };
}

/**
 * 获取文章详情
 */
export function useArticleDetail(id: number) {
  const [data, setData] = useState<Article | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!id) return;

    const fetchData = async () => {
      setLoading(true);
      try {
        const api = getApiClient();
        const response = await api.get<ApiResponse<Article>>('/article/detail', {
          params: { id }
        });
        if (response.code === 1) {
          setData(response.data);
        } else {
          setError(response.msg);
        }
      } catch (e: any) {
        setError(e.message);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [id]);

  return { data, loading, error };
}

/**
 * 获取推荐文章
 */
export function useArticleRecommend(params: { lang?: string; columnId?: number; limit?: number } = {}) {
  const [data, setData] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      try {
        const api = getApiClient();
        const response = await api.get<ApiResponse<Article[]>>('/article/recommend', {
          params: { limit: 5, ...params }
        });
        if (response.code === 1) {
          setData(response.data);
        } else {
          setError(response.msg);
        }
      } catch (e: any) {
        setError(e.message);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [JSON.stringify(params)]);

  return { data, loading, error };
}

/**
 * 获取热门文章
 */
export function useArticleHot(params: { lang?: string; columnId?: number; limit?: number } = {}) {
  const [data, setData] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      try {
        const api = getApiClient();
        const response = await api.get<ApiResponse<Article[]>>('/article/hot', {
          params: { limit: 10, ...params }
        });
        if (response.code === 1) {
          setData(response.data);
        } else {
          setError(response.msg);
        }
      } catch (e: any) {
        setError(e.message);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [JSON.stringify(params)]);

  return { data, loading, error };
}

// ==================== 搜索 API Hooks ====================

/**
 * 搜索
 */
export function useSearch(params: SearchParams) {
  const [data, setData] = useState<SearchResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const search = useCallback(async () => {
    if (!params.keyword) return;

    setLoading(true);
    setError(null);
    try {
      const api = getApiClient();
      const response = await api.get<ApiResponse<SearchResult>>('/search', { params });
      if (response.code === 1) {
        setData(response.data);
      } else {
        setError(response.msg);
      }
    } catch (e: any) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }, [params.keyword, params.type, params.lang]);

  useEffect(() => {
    search();
  }, [search]);

  return { data, loading, error, search };
}

// ==================== 其他 Hooks ====================

/**
 * 获取幻灯片
 */
export function useSlides(group: string = 'default', limit: number = 10) {
  const [data, setData] = useState<Slide[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const api = getApiClient();
        const response = await api.get<ApiResponse<Slide[]>>('/other/slides', {
          params: { group, limit }
        });
        if (response.code === 1) {
          setData(response.data);
        }
      } catch (e) {
        console.error(e);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [group, limit]);

  return { data, loading };
}

/**
 * 获取友链
 */
export function useLinks(type: string = 'all', limit: number = 20) {
  const [data, setData] = useState<Link[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const api = getApiClient();
        const response = await api.get<ApiResponse<Link[]>>('/other/links', {
          params: { type, limit }
        });
        if (response.code === 1) {
          setData(response.data);
        }
      } catch (e) {
        console.error(e);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [type, limit]);

  return { data, loading };
}

/**
 * 获取导航
 */
export function useNav(position: string = 'header', lang?: string) {
  const [data, setData] = useState<Nav[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const api = getApiClient();
        const response = await api.get<ApiResponse<Nav[]>>('/other/nav', {
          params: { position, lang }
        });
        if (response.code === 1) {
          setData(response.data);
        }
      } catch (e) {
        console.error(e);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [position, lang]);

  return { data, loading };
}

/**
 * 获取系统配置
 */
export function useConfig(key?: string) {
  const [data, setData] = useState<Record<string, any> | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const api = getApiClient();
        const response = await api.get('/other/config', {
          params: key ? { key } : {}
        });
        if (response.code === 1) {
          setData(response.data);
        }
      } catch (e) {
        console.error(e);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [key]);

  return { data, loading };
}

/**
 * 获取统计概览
 */
export function useStatsOverview(lang?: string) {
  const [data, setData] = useState<StatsOverview | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const api = getApiClient();
        const response = await api.get<ApiResponse<StatsOverview>>('/stats/overview', {
          params: { lang }
        });
        if (response.code === 1) {
          setData(response.data);
        }
      } catch (e) {
        console.error(e);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [lang]);

  return { data, loading };
}

/**
 * 获取热门标签
 */
export function useHotTags(limit: number = 20, lang?: string) {
  const [data, setData] = useState<Tag[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const api = getApiClient();
        const response = await api.get<ApiResponse<Tag[]>>('/tag/hot', {
          params: { limit, lang }
        });
        if (response.code === 1) {
          setData(response.data);
        }
      } catch (e) {
        console.error(e);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [limit, lang]);

  return { data, loading };
}

// ==================== 全局 API 客户端实例 ====================

let globalApiClient: AxiosInstance | null = null;

/**
 * 获取 API 客户端实例
 */
export function getApiClient(): AxiosInstance {
  if (!globalApiClient) {
    const baseURL = import.meta.env?.VITE_API_URL || process.env?.REACT_APP_API_URL || '/api';
    globalApiClient = createApiClient({
      baseURL,
      timeout: 10000,
      getAuthToken: () => localStorage.getItem('token'),
      onAuthError: () => {
        localStorage.removeItem('token');
        window.location.href = '/login';
      },
      onRateLimit: () => {
        console.warn('请求频率超限，请稍后再试');
      }
    });
  }
  return globalApiClient;
}

// ==================== 导出 ====================

export {
  // 默认导出所有 hooks
  useColumnList,
  useColumnTree,
  useColumnDetail,
  useBreadcrumb,
  useArticleList,
  useArticleDetail,
  useArticleRecommend,
  useArticleHot,
  useSearch,
  useSlides,
  useLinks,
  useNav,
  useConfig,
  useStatsOverview,
  useHotTags
};
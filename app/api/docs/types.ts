/**
 * FoxCMS API TypeScript 类型定义
 *
 * @version 1.0.0
 * @description 为前端开发提供完整的 TypeScript 类型支持
 */

// ==================== 通用响应类型 ====================

/**
 * API 响应基类
 */
export interface ApiResponse<T = any> {
  code: number;
  msg: string;
  data: T;
  time: number;
  requestId: string;
}

/**
 * 分页响应
 */
export interface PaginatedResponse<T> extends ApiResponse<PaginatedData<T>> {}

/**
 * 分页数据
 */
export interface PaginatedData<T> {
  list: T[];
  pagination: Pagination;
}

/**
 * 分页信息
 */
export interface Pagination {
  page: number;
  pageSize: number;
  total: number;
  totalPages: number;
  hasMore: boolean;
}

// ==================== 栏目相关类型 ====================

/**
 * 栏目数据
 */
export interface Column {
  id: number;
  pid: number;
  name: string;
  model: string;
  type: number;
  seoTitle?: string;
  seoKeyword?: string;
  seoDesc?: string;
  urlname: string;
  picUrl?: string;
  content?: string;
  lang?: string;
}

/**
 * 栏目树节点
 */
export interface ColumnTree extends Column {
  children: ColumnTree[];
}

/**
 * 面包屑项
 */
export interface Breadcrumb {
  id: number;
  name: string;
  urlname: string;
}

// ==================== 文章相关类型 ====================

/**
 * 文章数据
 */
export interface Article {
  id: number;
  columnId: number;
  columnName?: string;
  title: string;
  subtitle?: string;
  description?: string;
  content?: string;
  author?: string;
  source?: string;
  click: number;
  releaseTime: string;
  imgUrl?: string;
  tags: string[];
  isRecommend?: boolean;
  isTop?: boolean;
  isHot?: boolean;
  articleField?: string;
}

/**
 * 文章列表项（简化版）
 */
export interface ArticleListItem {
  id: number;
  columnId: number;
  title: string;
  subtitle?: string;
  description?: string;
  author?: string;
  source?: string;
  click: number;
  releaseTime: string;
  imgUrl?: string;
  tags: string[];
  isRecommend?: boolean;
  isTop?: boolean;
  isHot?: boolean;
}

// ==================== 产品相关类型 ====================

/**
 * 产品数据
 */
export interface Product {
  id: number;
  columnId: number;
  columnName?: string;
  title: string;
  subtitle?: string;
  description?: string;
  content?: string;
  price?: string;
  marketPrice?: string;
  productNo?: string;
  stock?: number;
  click: number;
  releaseTime: string;
  imgUrl?: string;
  images?: UploadFile[];
  picSet?: any[];
  tags?: string[];
  articleField?: string;
  params?: any[];
}

/**
 * 产品列表项（简化版）
 */
export interface ProductListItem {
  id: number;
  columnId: number;
  title: string;
  subtitle?: string;
  description?: string;
  price?: string;
  marketPrice?: string;
  productNo?: string;
  click: number;
  releaseTime: string;
  imgUrl?: string;
  tags?: string[];
  isRecommend?: boolean;
}

// ==================== 图片/视频/下载类型 ====================

/**
 * 图片数据
 */
export interface Images {
  id: number;
  columnId: number;
  title: string;
  description?: string;
  imgUrl?: string;
  content?: string;
  click: number;
  releaseTime: string;
  images?: UploadFile[];
}

/**
 * 视频数据
 */
export interface Video {
  id: number;
  columnId: number;
  title: string;
  description?: string;
  content?: string;
  videoUrl?: string;
  click: number;
  releaseTime: string;
  imgUrl?: string;
  images?: UploadFile[];
}

/**
 * 下载数据
 */
export interface Download {
  id: number;
  columnId: number;
  title: string;
  description?: string;
  content?: string;
  version?: string;
  fileSize?: string;
  downUrl?: string;
  click: number;
  releaseTime: string;
  imgUrl?: string;
}

// ==================== 其他内容类型 ====================

/**
 * 上传文件
 */
export interface UploadFile {
  url: string;
  name?: string;
  size?: number;
}

/**
 * 幻灯片
 */
export interface Slide {
  id: number;
  title: string;
  picUrl: string;
  link?: string;
  target?: string;
}

/**
 * 友情链接
 */
export interface Link {
  id: number;
  name: string;
  type: string;
  url: string;
  logo?: string;
}

/**
 * 导航菜单
 */
export interface Nav {
  id: number;
  name: string;
  url: string;
  target?: string;
}

/**
 * 标签
 */
export interface Tag {
  id: number;
  name: string;
  group?: string;
}

/**
 * 单页内容
 */
export interface SinglePage {
  id: number;
  columnId: number;
  title: string;
  content?: string;
  seoTitle?: string;
  seoKeyword?: string;
  seoDesc?: string;
}

// ==================== 统计相关类型 ====================

/**
 * 统计概览
 */
export interface StatsOverview {
  article: number;
  product: number;
  images: number;
  video: number;
  download: number;
  column: number;
  total: number;
}

/**
 * 热门内容
 */
export interface PopularItem {
  id: number;
  columnId: number;
  title: string;
  click: number;
  imgUrl?: string;
  releaseTime: string;
}

/**
 * 访问统计
 */
export interface VisitStats {
  totalVisits: number;
  todayVisits: number;
  yesterdayVisits: number;
}

/**
 * 栏目统计
 */
export interface ColumnStats {
  id: number;
  pid: number;
  name: string;
  model: string;
  articleCount: number;
  productCount: number;
  totalCount: number;
}

// ==================== 反馈相关类型 ====================

/**
 * 反馈数据
 */
export interface Feedback {
  id: number;
  type: string;
  title: string;
  content: string;
  contact?: string;
  email?: string;
  phone?: string;
  status: number;
  reply?: string;
  replyTime?: string;
  createTime: string;
  lang?: string;
}

/**
 * 反馈统计
 */
export interface FeedbackStats {
  total: number;
  pending: number;
  processing: number;
  resolved: number;
}

// ==================== 搜索相关类型 ====================

/**
 * 搜索结果
 */
export interface SearchResult {
  article?: {
    list: any[];
    pagination: Pagination;
  };
  product?: {
    list: any[];
    pagination: Pagination;
  };
  images?: {
    list: any[];
    pagination: Pagination;
  };
  video?: {
    list: any[];
    pagination: Pagination;
  };
  download?: {
    list: any[];
    pagination: Pagination;
  };
}

// ==================== 请求参数类型 ====================

/**
 * 分页请求参数
 */
export interface PageParams {
  page?: number;
  pageSize?: number;
}

/**
 * 列表请求参数
 */
export interface ListParams extends PageParams {
  lang?: string;
  columnId?: number;
  recommend?: string;
}

/**
 * 详情请求参数
 */
export interface DetailParams {
  id: number;
}

/**
 * 文章列表请求参数
 */
export interface ArticleListParams extends ListParams {
  tag?: string;
}

/**
 * 搜索请求参数
 */
export interface SearchParams extends PageParams {
  keyword: string;
  type?: 'all' | 'article' | 'product' | 'images' | 'video' | 'download';
  lang?: string;
}

/**
 * 标签文章请求参数
 */
export interface TagContentParams extends PageParams {
  tag: string;
  lang?: string;
}

// ==================== 状态码 ====================

/**
 * API 状态码枚举
 */
export enum ApiCode {
  SUCCESS = 1,
  FAIL = 0,
  INVALID_PARAM = 1001,
  MISSING_PARAM = 1002,
  UNAUTHORIZED = 1003,
  FORBIDDEN = 1004,
  NOT_FOUND = 1005,
  METHOD_NOT_ALLOWED = 1006,
  REQUEST_TIMEOUT = 1007,
  RATE_LIMIT_EXCEEDED = 1008,
  INVALID_SIGNATURE = 1009,
  TOKEN_EXPIRED = 1010,
  TOKEN_INVALID = 1011,
  SERVER_ERROR = 2001,
  DATABASE_ERROR = 2002,
  CACHE_ERROR = 2003
}

/**
 * 状态码消息映射
 */
export const ApiCodeMessage: Record<ApiCode, string> = {
  [ApiCode.SUCCESS]: 'success',
  [ApiCode.FAIL]: 'fail',
  [ApiCode.INVALID_PARAM]: 'Invalid parameter',
  [ApiCode.MISSING_PARAM]: 'Missing required parameter',
  [ApiCode.UNAUTHORIZED]: 'Unauthorized',
  [ApiCode.FORBIDDEN]: 'Access forbidden',
  [ApiCode.NOT_FOUND]: 'Resource not found',
  [ApiCode.METHOD_NOT_ALLOWED]: 'Method not allowed',
  [ApiCode.REQUEST_TIMEOUT]: 'Request timeout',
  [ApiCode.RATE_LIMIT_EXCEEDED]: 'Rate limit exceeded, please try again later',
  [ApiCode.INVALID_SIGNATURE]: 'Invalid signature',
  [ApiCode.TOKEN_EXPIRED]: 'Token expired',
  [ApiCode.TOKEN_INVALID]: 'Invalid token',
  [ApiCode.SERVER_ERROR]: 'Internal server error',
  [ApiCode.DATABASE_ERROR]: 'Database error',
  [ApiCode.CACHE_ERROR]: 'Cache error'
};

// ==================== API 请求封装类型 ====================

/**
 * Axios 配置选项
 */
export interface AxiosConfig {
  baseURL?: string;
  timeout?: number;
  headers?: Record<string, string>;
}

/**
 * 请求错误
 */
export interface ApiError {
  code: number;
  message: string;
  response?: any;
}

// ==================== 工具类型 ====================

/**
 * 判断是否为成功响应
 */
export function isSuccessResponse<T>(response: ApiResponse<T>): boolean {
  return response.code === ApiCode.SUCCESS;
}

/**
 * 获取状态码消息
 */
export function getCodeMessage(code: number): string {
  return ApiCodeMessage[code] || 'Unknown error';
}

/**
 * 创建类型守卫
 */
export function isPaginatedResponse<T>(
  response: any
): response is PaginatedResponse<T> {
  return (
    response &&
    response.code === 1 &&
    response.data &&
    Array.isArray(response.data.list) &&
    response.data.pagination
  );
}
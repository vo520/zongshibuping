# 总是不平

基于 FoxCMS 的内容管理系统

## 项目简介

总是不平是一个使用 FoxCMS 框架构建的内容管理网站，支持文章管理、产品展示、图片画廊、视频播放等多种内容类型。

## 技术栈

- 后端框架: ThinkPHP 6.x
- 前端: HTML5 + CSS3 + JavaScript
- 数据库: MySQL
- Web服务器: Apache/Nginx

## 功能特性

- 可视化后台管理
- 多语言支持
- SEO 优化
- 响应式设计
- 会员系统
- 表单管理
- 搜索功能
- 网站地图生成
- 静态页面生成

## 目录结构

app/              - 应用目录
  admin/          - 后台管理
  api/            - API 接口
  common/         - 公共模块
  home/           - 前台展示
  taglib/         - 标签库
config/           - 配置文件
custom/           - 自定义开发
extend/           - 扩展类库
static/           - 静态资源
templates/        - 模板文件
install/          - 安装程序
index.php         - 入口文件

## 快速开始

### 环境要求

- PHP >= 7.2
- MySQL >= 5.7
- Apache 或 Nginx

### 安装步骤

1. 克隆仓库到本地
2. 配置数据库连接（config/database.php）
3. 访问 install/ 目录完成安装
4. 按照提示完成系统配置

### 默认账号

- 后台地址: /admin.php
- 用户名: admin
- 密码: admin123

## 开发指南

### 模板开发

模板文件位于 templates/ 目录，使用 FoxCMS 标签库进行开发

### 控制器开发

控制器位于 app/admin/controller/ 或 app/home/controller/

## 注意事项

- 请在生产环境中修改默认后台密码
- 定期备份数据库和文件
- config/cfg/ 和 data/ 目录包含敏感配置

## 许可证

本项目采用 MIT 许可证

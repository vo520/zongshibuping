# zongshibuping

请按以下步骤创建 GitHub 仓库并推送代码：

## 步骤 1：在 GitHub 网站上创建仓库

1. 登录 GitHub：https://github.com
2. 点击右上角的 **+** 号，选择 **New repository**
3. 仓库名称填写：`zongshibuping`
4. 可以选择 Public 或 Private
5. **不要**勾选 "Add a README file"
6. 点击 **Create repository**

## 步骤 2：获取仓库地址

创建后，GitHub 会显示仓库地址，例如：
- HTTPS: `https://github.com/你的用户名/zongshibuping.git`
- SSH: `git@github.com:你的用户名/zongshibuping.git`

## 步骤 3：添加远程仓库并推送

在你的终端运行以下命令（将 `你的用户名` 替换为你的 GitHub 用户名）：

```bash
cd /Users/mq/Sites/public_html

# 添加远程仓库（使用 SSH 方式）
git remote add origin git@github.com:你的用户名/zongshibuping.git

# 推送代码
git branch -M main
git push -u origin main
```

## 验证 SSH 密钥（可选）

如果你遇到 SSH 连接问题，先运行以下命令确认 GitHub 连接：

```bash
ssh -T git@github.com
```

如果提示 "Hi [username]! You've successfully authenticated"，表示配置成功。
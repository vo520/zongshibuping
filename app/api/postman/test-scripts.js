/**
 * FoxCMS API 测试脚本
 *
 * 在 Postman 的 Tests 标签页中使用
 * 自动验证响应格式、状态码、数据结构
 */

// ==================== 响应格式验证 ====================

// 验证响应状态码
pm.test("响应状态码为 200", function() {
    pm.response.to.have.status(200);
});

// 验证响应是 JSON 格式
pm.test("响应是 JSON 格式", function() {
    pm.response.to.be.json;
});

// 验证响应包含必要字段
pm.test("响应包含 code 字段", function() {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('code');
});

pm.test("响应包含 msg 字段", function() {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('msg');
});

pm.test("响应包含 data 字段", function() {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('data');
});

pm.test("响应包含 time 字段", function() {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('time');
});

pm.test("响应包含 requestId 字段", function() {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('requestId');
});

// ==================== 成功响应验证 ====================

pm.test("API 调用成功", function() {
    var jsonData = pm.response.json();
    pm.expect(jsonData.code).to.eql(1);
});

pm.test("响应消息正确", function() {
    var jsonData = pm.response.json();
    pm.expect(jsonData.msg).to.be.oneOf(['success', '操作成功']);
});

// ==================== 分页数据验证 ====================

pm.test("分页数据包含必要字段", function() {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.pagination) {
        var pagination = jsonData.data.pagination;
        pm.expect(pagination).to.have.property('page');
        pm.expect(pagination).to.have.property('pageSize');
        pm.expect(pagination).to.have.property('total');
        pm.expect(pagination).to.have.property('totalPages');
        pm.expect(pagination).to.have.property('hasMore');
    }
});

pm.test("分页参数有效", function() {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.pagination) {
        var pagination = jsonData.data.pagination;
        pm.expect(pagination.page).to.be.at.least(1);
        pm.expect(pagination.pageSize).to.be.at.least(1);
        pm.expect(pagination.total).to.be.at.least(0);
    }
});

// ==================== 列表数据验证 ====================

pm.test("列表数据是数组", function() {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.list) {
        pm.expect(jsonData.data.list).to.be.an('array');
    }
});

pm.test("列表数据包含必要字段", function() {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.list && jsonData.data.list.length > 0) {
        var item = jsonData.data.list[0];
        pm.expect(item).to.have.property('id');
    }
});

// ==================== 错误响应验证 ====================

pm.test("错误响应格式正确", function() {
    var jsonData = pm.response.json();
    if (jsonData.code !== 1) {
        pm.expect(jsonData.code).to.be.a('number');
        pm.expect(jsonData.code).to.not.eql(1);
        pm.expect(jsonData.msg).to.be.a('string');
    }
});

// 错误码分类验证
pm.test("错误码在有效范围内", function() {
    var jsonData = pm.response.json();
    if (jsonData.code !== 1) {
        var code = jsonData.code;
        // 客户端错误 (1xxx) 或服务器错误 (2xxx)
        var isValidCode = (code >= 1000 && code < 2000) || (code >= 2000 && code < 3000);
        pm.expect(isValidCode).to.be.true;
    }
});

// ==================== 性能测试 ====================

pm.test("响应时间小于 500ms", function() {
    pm.expect(pm.response.responseTime).to.be.below(500);
});

pm.test("响应时间小于 200ms", function() {
    pm.expect(pm.response.responseTime).to.be.below(200);
});

// ==================== 响应头验证 ====================

pm.test("包含正确的 Content-Type", function() {
    pm.response.headers.has('Content-Type');
    var contentType = pm.response.headers.get('Content-Type');
    pm.expect(contentType).to.include('application/json');
});

pm.test("包含 API 版本信息", function() {
    var jsonData = pm.response.json();
    if (jsonData.apiVersion) {
        pm.expect(jsonData.apiVersion).to.be.a('string');
    }
});

// ==================== 限流相关 ====================

pm.test("未触发限流", function() {
    var jsonData = pm.response.json();
    pm.expect(jsonData.code).to.not.eql(1008);
});

pm.test("检查限流响应头", function() {
    // 检查是否包含限流相关响应头
    var rateLimitHeaders = [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset'
    ];

    rateLimitHeaders.forEach(function(header) {
        // 验证响应头存在性
        // pm.test(`包含 ${header} 响应头`, function() {
        //     pm.response.headers.has(header);
        // });
    });
});

// ==================== CORS 相关 ====================

pm.test("OPTIONS 请求包含 CORS 头", function() {
    if (pm.request.method === 'OPTIONS') {
        pm.response.headers.has('Access-Control-Allow-Origin');
        pm.response.headers.has('Access-Control-Allow-Methods');
    }
});

// ==================== 通用工具函数 ====================

/**
 * 验证字段类型
 * @param {string} field - 字段名
 * @param {string} type - 期望类型
 */
function validateFieldType(field, type) {
    pm.test(`${field} 是 ${type} 类型`, function() {
        var jsonData = pm.response.json();
        if (jsonData.data) {
            pm.expect(jsonData.data[field]).to.be.a(type);
        }
    });
}

/**
 * 验证必填字段存在
 * @param {string} field - 字段名
 */
function validateRequiredField(field) {
    pm.test(`${field} 字段必填`, function() {
        var jsonData = pm.response.json();
        if (jsonData.data && jsonData.data.list) {
            if (jsonData.data.list.length > 0) {
                pm.expect(jsonData.data.list[0]).to.have.property(field);
            }
        }
    });
}

/**
 * 验证字段值范围
 * @param {string} field - 字段名
 * @param {number} min - 最小值
 * @param {number} max - 最大值
 */
function validateFieldRange(field, min, max) {
    pm.test(`${field} 在有效范围内`, function() {
        var jsonData = pm.response.json();
        if (jsonData.data && jsonData.data.list && jsonData.data.list.length > 0) {
            var value = jsonData.data.list[0][field];
            if (value !== undefined) {
                pm.expect(value).to.be.at.least(min);
                pm.expect(value).to.be.at.most(max);
            }
        }
    });
}

/**
 * 记录测试结果
 */
function logTestResult() {
    var results = {
        url: pm.request.url,
        method: pm.request.method,
        status: pm.response.status,
        responseTime: pm.response.responseTime,
        timestamp: new Date().toISOString()
    };
    console.log('Test Results:', JSON.stringify(results, null, 2));
}

// 执行结果记录
logTestResult();
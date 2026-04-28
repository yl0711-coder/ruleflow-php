# Decision List 决策列表模型

RuleFlow 基于 Decision List（决策列表）模型。

Decision List 是一组有顺序的规则：

```text
如果 rule_1 命中，返回 decision_1
否则如果 rule_2 命中，返回 decision_2
否则如果 rule_3 命中，返回 decision_3
否则返回未命中
```

它也可以理解为“优先级规则列表”。这个模型适合输入上下文固定、输出决策确定的业务判断场景。

## RuleFlow 如何映射到这个模型

RuleFlow 的模型是直接映射的：

- 一个 rule set 就是一份决策列表。
- 每条 rule 都有 `priority`。
- 规则按 priority 从高到低执行。
- 每条规则由布尔谓词条件组成。
- `evaluate()` 返回第一条命中的规则。
- `evaluateAll()` 用于收集所有命中规则，适合非互斥业务场景。
- `trace()` 记录每条规则为什么通过或失败。
- `explain()` 把 trace 转成更紧凑的运行摘要。

示例：

```php
[
    [
        'name' => 'block_high_risk_order',
        'priority' => 100,
        'conditions' => [
            ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
            ['field' => 'user.risk_score', 'operator' => '<', 'value' => 60],
        ],
        'action' => 'reject',
    ],
    [
        'name' => 'review_new_user',
        'priority' => 50,
        'conditions' => [
            ['field' => 'user.days_since_signup', 'operator' => '<=', 'value' => 7],
        ],
        'action' => 'manual_review',
    ],
]
```

使用 `evaluate()` 时，第一条命中的规则就是最终决策。

## 谓词组合

每个条件都是一个布尔谓词：

```text
字段 操作符 期望值
```

示例：

```text
order.amount > 1000
user.country in ["NG", "RU"]
user.phone exists
```

谓词可以通过以下方式组合：

- `all`：所有条件都必须通过
- `any`：至少一个条件通过
- 嵌套条件组，例如 `A AND (B OR C)`

这能覆盖常见 PHP 业务决策，同时不引入完整推理引擎的复杂度。

## 为什么不是 RETE

RuleFlow 不是 RETE 引擎。

Drools 这类 RETE 引擎适合大量事实、大量规则、事实增量变化和推理行为。它们适合规则之间会互相激活，或者需要维护 working memory 的专家系统场景。

RuleFlow 解决的是另一个问题：

```text
给定一次请求上下文，产生一个可解释的业务决策。
```

在很多 PHP 和 Laravel 系统里，主要痛点不是增量规则匹配，而是：

- 业务规则散落在 controller、service、job、listener 里
- 规则改动不安全
- 发布前缺少规则校验
- 决策产生后说不清为什么命中
- 在第一条有价值规则上线前，引入大平台成本太高

RuleFlow 有意选择 Decision List 模型，因为它让规则执行保持确定、可审查、可测试、可解释。

## 优点

- 执行顺序确定。
- 规则格式小，适合 code review。
- 不需要额外服务、数据库或可视化设计器。
- 适合请求级业务决策。
- trace 能解释命中、失败、字段缺失、跳过规则和耗时。
- 规则校验可以在运行前拦截错误定义。
- Laravel 集成是可选能力，核心保持框架无关。

## 取舍

- 规则评估复杂度与启用规则数量线性相关。
- `evaluateAll()` 比 `evaluate()` 更重，因为它不会在第一条命中后停止。
- 不提供 RETE 风格的增量匹配。
- 不实现 forward chaining 或 backward chaining。
- 不是复杂事件处理引擎。
- 不是工作流或业务编排平台。

## 复杂度

`evaluate()`：

```text
O(第一条命中前检查的规则数 * 每条规则条件数)
```

`evaluateAll()`：

```text
O(所有启用规则数 * 每条规则条件数)
```

实际使用中，它适合 PHP 服务里的小型和中型规则集。如果规则数量变大，建议按业务域拆分 rule set，把高置信度规则放前面，并用 `benchmarks/evaluate.php` 测量。

## 什么时候适合使用 RuleFlow

适合：

- 风控或反欺诈决策
- 内容审核分流
- 活动资格判断
- 基于上下文的访问决策
- 需要给客服、运营或审核人员解释的业务决策
- 需要测试和 code review 的规则层

不适合：

- 完整专家系统
- 复杂事件处理
- working-memory 推理
- 可视化规则管理平台
- 大规模规则网络优化


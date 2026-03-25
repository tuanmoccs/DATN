import React, {useState, useEffect, useCallback} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  StatusBar,
  RefreshControl,
} from 'react-native';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import assignmentService, {AssignmentInfo} from '../../services/assignmentService';
import {MainStackParamList} from '../../navigation/MainNavigator';

type AssignmentListRouteProp = RouteProp<MainStackParamList, 'AssignmentList'>;
type NavigationProp = NativeStackNavigationProp<MainStackParamList>;

const AssignmentListScreen: React.FC = () => {
  const route = useRoute<AssignmentListRouteProp>();
  const navigation = useNavigation<NavigationProp>();
  const {classId, className} = route.params;

  const [assignments, setAssignments] = useState<AssignmentInfo[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchAssignments = useCallback(
    async (isRefresh = false) => {
      if (isRefresh) {
        setRefreshing(true);
      } else {
        setLoading(true);
      }
      try {
        const response = await assignmentService.getAssignmentsByClass(classId);
        if (response.success) {
          setAssignments(response.data);
        }
      } catch (error) {
        console.error('Error fetching assignments:', error);
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [classId],
  );

  useEffect(() => {
    fetchAssignments();
  }, [fetchAssignments]);

  // Refresh khi quay lại từ submit
  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', () => {
      fetchAssignments(true);
    });
    return unsubscribe;
  }, [navigation, fetchAssignments]);

  const getStatusInfo = (assignment: AssignmentInfo) => {
    const submission = assignment.submissions?.[0];
    if (submission) {
      if (submission.grading?.score !== null && submission.grading?.score !== undefined) {
        return {
          label: `${submission.grading.score}/${submission.grading.max_score}`,
          color: '#10B981',
          bgColor: '#D1FAE5',
          icon: '✅',
        };
      }
      if (submission.grading?.ai_status === 'completed') {
        return {
          label: 'Đã chấm AI',
          color: '#8B5CF6',
          bgColor: '#EDE9FE',
          icon: '🤖',
        };
      }
      return {
        label: 'Đã nộp',
        color: '#2563EB',
        bgColor: '#DBEAFE',
        icon: '📤',
      };
    }

    if (assignment.due_date) {
      const dueDate = new Date(assignment.due_date);
      if (dueDate < new Date()) {
        return {
          label: 'Quá hạn',
          color: '#EF4444',
          bgColor: '#FEE2E2',
          icon: '⏰',
        };
      }
    }

    return {
      label: 'Chưa nộp',
      color: '#F59E0B',
      bgColor: '#FEF3C7',
      icon: '📝',
    };
  };

  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return 'Không có hạn';
    const date = new Date(dateStr);
    return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1)
      .toString()
      .padStart(2, '0')}/${date.getFullYear()} ${date
      .getHours()
      .toString()
      .padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
  };

  const getDaysRemaining = (dueDate: string | null) => {
    if (!dueDate) return null;
    const due = new Date(dueDate);
    const now = new Date();
    const diff = due.getTime() - now.getTime();
    const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
    if (days < 0) return `Quá hạn ${Math.abs(days)} ngày`;
    if (days === 0) return 'Hôm nay';
    if (days === 1) return 'Còn 1 ngày';
    return `Còn ${days} ngày`;
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#2563EB" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0D47A1" />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <View style={styles.headerContent}>
          <Text style={styles.headerTitle}>Bài tập</Text>
          <Text style={styles.headerSubtitle} numberOfLines={1}>
            {className}
          </Text>
        </View>
      </View>

      {/* Content */}
      <ScrollView
        style={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => fetchAssignments(true)}
            colors={['#2563EB']}
          />
        }>
        {assignments.length === 0 ? (
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyIcon}>📋</Text>
            <Text style={styles.emptyTitle}>Chưa có bài tập</Text>
            <Text style={styles.emptySubtitle}>
              Giáo viên chưa đăng bài tập nào cho lớp này
            </Text>
          </View>
        ) : (
          assignments.map((assignment, index) => {
            const status = getStatusInfo(assignment);
            const daysRemaining = getDaysRemaining(assignment.due_date);

            return (
              <TouchableOpacity
                key={assignment.id}
                style={styles.assignmentCard}
                activeOpacity={0.7}
                onPress={() =>
                  navigation.navigate('AssignmentDetail', {
                    assignmentId: assignment.id,
                    classId,
                  })
                }>
                {/* Card Header */}
                <View style={styles.cardHeader}>
                  <View style={styles.cardNumberBadge}>
                    <Text style={styles.cardNumberText}>{index + 1}</Text>
                  </View>
                  <View
                    style={[
                      styles.statusBadge,
                      {backgroundColor: status.bgColor},
                    ]}>
                    <Text style={styles.statusIcon}>{status.icon}</Text>
                    <Text style={[styles.statusText, {color: status.color}]}>
                      {status.label}
                    </Text>
                  </View>
                </View>

                {/* Card Content */}
                <Text style={styles.assignmentTitle} numberOfLines={2}>
                  {assignment.title}
                </Text>
                {assignment.description && (
                  <Text style={styles.assignmentDesc} numberOfLines={2}>
                    {assignment.description}
                  </Text>
                )}

                {/* Card Footer */}
                <View style={styles.cardFooter}>
                  <View style={styles.footerItem}>
                    <Text style={styles.footerIcon}>📅</Text>
                    <Text style={styles.footerText}>
                      {formatDate(assignment.due_date)}
                    </Text>
                  </View>
                  {daysRemaining && (
                    <Text
                      style={[
                        styles.daysRemaining,
                        daysRemaining.startsWith('Quá')
                          ? styles.daysOverdue
                          : styles.daysLeft,
                      ]}>
                      {daysRemaining}
                    </Text>
                  )}
                </View>

                {/* Score display */}
                <View style={styles.cardMeta}>
                  <Text style={styles.metaText}>
                    Điểm tối đa: {assignment.max_score}
                  </Text>
                  {assignment.files?.length > 0 && (
                    <Text style={styles.metaText}>
                      📎 {assignment.files.length} tệp đính kèm
                    </Text>
                  )}
                </View>
              </TouchableOpacity>
            );
          })
        )}
        <View style={styles.bottomSpace} />
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F0F4F8',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F0F4F8',
  },
  header: {
    backgroundColor: '#0D47A1',
    paddingTop: 44,
    paddingBottom: 16,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
  },
  backButton: {
    width: 38,
    height: 38,
    borderRadius: 6,
    backgroundColor: 'rgba(255,255,255,0.15)',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  backIcon: {
    fontSize: 20,
    color: '#FFFFFF',
    fontWeight: 'bold',
  },
  headerContent: {
    flex: 1,
  },
  headerTitle: {
    fontSize: 19,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  headerSubtitle: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.7)',
    marginTop: 2,
  },
  content: {
    flex: 1,
    padding: 16,
  },
  emptyContainer: {
    alignItems: 'center',
    paddingVertical: 60,
  },
  emptyIcon: {
    fontSize: 48,
    marginBottom: 12,
  },
  emptyTitle: {
    fontSize: 17,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 8,
  },
  emptySubtitle: {
    fontSize: 13,
    color: '#94A3B8',
    textAlign: 'center',
  },
  assignmentCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 16,
    marginBottom: 10,
    shadowColor: '#0D47A1',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.05,
    shadowRadius: 3,
    elevation: 2,
    borderLeftWidth: 3,
    borderLeftColor: '#1565C0',
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  cardNumberBadge: {
    width: 26,
    height: 26,
    borderRadius: 4,
    backgroundColor: '#E3F2FD',
    justifyContent: 'center',
    alignItems: 'center',
  },
  cardNumberText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#0D47A1',
  },
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 4,
  },
  statusIcon: {
    fontSize: 11,
    marginRight: 4,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '600',
  },
  assignmentTitle: {
    fontSize: 15,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 4,
  },
  assignmentDesc: {
    fontSize: 12,
    color: '#64748B',
    lineHeight: 18,
    marginBottom: 10,
  },
  cardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
  },
  footerItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  footerIcon: {
    fontSize: 12,
    marginRight: 4,
  },
  footerText: {
    fontSize: 11,
    color: '#64748B',
  },
  daysRemaining: {
    fontSize: 11,
    fontWeight: '600',
  },
  daysOverdue: {
    color: '#EF4444',
  },
  daysLeft: {
    color: '#10B981',
  },
  cardMeta: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 8,
  },
  metaText: {
    fontSize: 11,
    color: '#94A3B8',
  },
  bottomSpace: {
    height: 40,
  },
});

export default AssignmentListScreen;

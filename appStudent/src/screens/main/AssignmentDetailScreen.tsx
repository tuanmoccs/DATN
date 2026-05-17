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
  Linking,
  Alert,
} from 'react-native';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import assignmentService, {
  AssignmentInfo,
  SubmissionInfo,
  GradingInfo,
} from '../../services/assignmentService';
import {MainStackParamList} from '../../navigation/MainNavigator';
import API_CONFIG from '../../config/api';

type AssignmentDetailRouteProp = RouteProp<MainStackParamList, 'AssignmentDetail'>;
type NavigationProp = NativeStackNavigationProp<MainStackParamList>;

const AssignmentDetailScreen: React.FC = () => {
  const route = useRoute<AssignmentDetailRouteProp>();
  const navigation = useNavigation<NavigationProp>();
  const {assignmentId, classId} = route.params;

  const [assignment, setAssignment] = useState<AssignmentInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchAssignment = useCallback(
    async (isRefresh = false) => {
      if (isRefresh) {
        setRefreshing(true);
      } else {
        setLoading(true);
      }
      try {
        const response = await assignmentService.getAssignmentsByClass(classId);
        if (response.success) {
          const found = response.data.find((a: AssignmentInfo) => a.id === assignmentId);
          if (found) {
            setAssignment(found);
          }
        }
      } catch (error) {
        console.error('Error fetching assignment detail:', error);
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [classId, assignmentId],
  );

  useEffect(() => {
    fetchAssignment();
  }, [fetchAssignment]);

  // Refresh khi quay lại từ submit
  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', () => {
      fetchAssignment(true);
    });
    return unsubscribe;
  }, [navigation, fetchAssignment]);

  const submission: SubmissionInfo | null = assignment?.submissions?.[0] || null;
  const grading: GradingInfo | null = submission?.grading || null;
  const hasSubmitted = !!submission;

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

  const isOverdue = () => {
    if (!assignment?.due_date) return false;
    return new Date(assignment.due_date) < new Date();
  };

  const canSubmit = () => {
    if (hasSubmitted) return false;
    if (isOverdue() && !assignment?.allow_late_submission) return false;
    return true;
  };

  const getStorageUrl = (path: string) => {
    const baseUrl = API_CONFIG.BASE_URL.replace('/api', '');
    return `${baseUrl}/storage/${path}`;
  };

  const openFile = (filePath: string) => {
    const url = getStorageUrl(filePath);
    Linking.openURL(url).catch(() => {
      Alert.alert('Lỗi', 'Không thể mở tệp');
    });
  };

  const getFileIcon = (mimeType: string) => {
    if (mimeType.includes('image')) return '🖼️';
    if (mimeType.includes('pdf')) return '📄';
    if (mimeType.includes('word') || mimeType.includes('document')) return '📝';
    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return '📊';
    if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return '📊';
    return '📎';
  };

  const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#0D47A1" />
      </View>
    );
  }

  if (!assignment) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorIcon}>😔</Text>
        <Text style={styles.errorText}>Không tìm thấy bài tập</Text>
        <TouchableOpacity
          style={styles.retryButton}
          onPress={() => fetchAssignment()}>
          <Text style={styles.retryButtonText}>Thử lại</Text>
        </TouchableOpacity>
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
          <Text style={styles.headerTitle} numberOfLines={1}>
            {assignment.title}
          </Text>
          <Text style={styles.headerSubtitle}>Chi tiết bài tập</Text>
        </View>
      </View>

      <ScrollView
        style={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => fetchAssignment(true)}
            colors={['#0D47A1']}
          />
        }>
        {/* Assignment Info Card */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>📋 Thông tin bài tập</Text>

          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>Hạn nộp</Text>
            <Text
              style={[
                styles.infoValue,
                isOverdue() && styles.textDanger,
              ]}>
              {formatDate(assignment.due_date)}
            </Text>
          </View>

          <View style={styles.divider} />

          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>Điểm tối đa</Text>
            <Text style={styles.infoValue}>{assignment.max_score}</Text>
          </View>

          <View style={styles.divider} />

          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>Loại nộp bài</Text>
            <Text style={styles.infoValue}>
              {assignment.submission_type === 'file'
                ? 'Tệp đính kèm'
                : assignment.submission_type === 'text'
                ? 'Văn bản'
                : 'Tệp hoặc văn bản'}
            </Text>
          </View>

          {assignment.allow_late_submission && (
            <>
              <View style={styles.divider} />
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Nộp muộn</Text>
                <Text style={styles.infoValue}>
                  Cho phép (trừ {assignment.late_penalty}%)
                </Text>
              </View>
            </>
          )}
        </View>

        {/* Description */}
        {assignment.description && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>📝 Mô tả</Text>
            <Text style={styles.descriptionText}>{assignment.description}</Text>
          </View>
        )}

        {/* Instructions */}
        {assignment.instructions && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>📖 Hướng dẫn</Text>
            <Text style={styles.descriptionText}>{assignment.instructions}</Text>
          </View>
        )}

        {/* Attached Files */}
        {assignment.files && assignment.files.length > 0 && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>
              📎 Tệp đính kèm ({assignment.files.length})
            </Text>
            {assignment.files.map(file => (
              <TouchableOpacity
                key={file.id}
                style={styles.fileItem}
                onPress={() => openFile(file.file_path)}>
                <Text style={styles.fileIcon}>
                  {getFileIcon(file.mime_type)}
                </Text>
                <View style={styles.fileInfo}>
                  <Text style={styles.fileName} numberOfLines={1}>
                    {file.file_name}
                  </Text>
                  <Text style={styles.fileSize}>
                    {formatFileSize(file.file_size)}
                  </Text>
                </View>
                <Text style={styles.downloadIcon}>⬇️</Text>
              </TouchableOpacity>
            ))}
          </View>
        )}

        {/* Submission Status */}
        {hasSubmitted ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>📤 Bài nộp của bạn</Text>

            <View
              style={[
                styles.submissionStatusBanner,
                submission.is_late
                  ? styles.bannerWarning
                  : styles.bannerSuccess,
              ]}>
              <Text style={styles.bannerIcon}>
                {submission.is_late ? '⚠️' : '✅'}
              </Text>
              <View>
                <Text style={styles.bannerTitle}>
                  {submission.is_late ? 'Đã nộp (Trễ hạn)' : 'Đã nộp bài'}
                </Text>
                <Text style={styles.bannerSubtitle}>
                  {formatDate(submission.submitted_at)}
                </Text>
              </View>
            </View>

            {/* Submitted Attachments */}
            {submission.attachments?.length > 0 && (
              <View style={styles.attachmentsSection}>
                <Text style={styles.sectionLabel}>Tệp đã nộp:</Text>
                {submission.attachments.map(att => (
                  <TouchableOpacity
                    key={att.id}
                    style={styles.fileItem}
                    onPress={() => openFile(att.file_path)}>
                    <Text style={styles.fileIcon}>
                      {getFileIcon(att.mime_type)}
                    </Text>
                    <View style={styles.fileInfo}>
                      <Text style={styles.fileName} numberOfLines={1}>
                        {att.file_name}
                      </Text>
                      <Text style={styles.fileSize}>
                        {formatFileSize(att.file_size)}
                      </Text>
                    </View>
                  </TouchableOpacity>
                ))}
              </View>
            )}
          </View>
        ) : (
          /* Submit Button */
          canSubmit() && (
            <TouchableOpacity
              style={styles.submitButton}
              activeOpacity={0.8}
              onPress={() =>
                navigation.navigate('AssignmentSubmit', {
                  assignmentId: assignment.id,
                  assignmentTitle: assignment.title,
                  submissionType: assignment.submission_type,
                  maxScore: assignment.max_score,
                })
              }>
              <Text style={styles.submitButtonIcon}>📤</Text>
              <Text style={styles.submitButtonText}>Nộp bài tập</Text>
            </TouchableOpacity>
          )
        )}

        {/* Overdue warning */}
        {!hasSubmitted && isOverdue() && !assignment.allow_late_submission && (
          <View style={[styles.card, styles.cardDanger]}>
            <Text style={styles.cardTitle}>⏰ Đã quá hạn nộp</Text>
            <Text style={styles.descriptionText}>
              Bài tập này đã quá hạn nộp và không cho phép nộp muộn.
            </Text>
          </View>
        )}

        {/* Grading Section */}
        {grading && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>📊 Kết quả chấm điểm</Text>

            {/* Final Score */}
            {grading.score !== null && grading.score !== undefined ? (
              <View style={styles.scoreSection}>
                <View style={styles.scoreCircle}>
                  <Text style={styles.scoreValue}>{grading.score}</Text>
                  <Text style={styles.scoreMax}>/{grading.max_score}</Text>
                </View>
                <Text style={styles.scorePercentage}>
                  {((grading.score / grading.max_score) * 100).toFixed(1)}%
                </Text>
                {grading.feedback && (
                  <View style={styles.feedbackBox}>
                    <Text style={styles.feedbackLabel}>
                      💬 Nhận xét của giáo viên
                    </Text>
                    <Text style={styles.feedbackText}>{grading.feedback}</Text>
                  </View>
                )}
              </View>
            ) : (
              <View style={styles.pendingGrading}>
                <Text style={styles.pendingIcon}>⏳</Text>
                <Text style={styles.pendingText}>
                  Giáo viên chưa chấm điểm
                </Text>
              </View>
            )}

            {/* AI Grading Results */}
            {grading.ai_status === 'completed' &&
              grading.ai_suggested_score !== null && (
                <View style={styles.aiSection}>
                  <View style={styles.aiHeader}>
                    <Text style={styles.aiHeaderIcon}>🤖</Text>
                    <Text style={styles.aiHeaderTitle}>
                      Gợi ý từ AI
                    </Text>
                  </View>

                  <View style={styles.aiScoreRow}>
                    <Text style={styles.aiScoreLabel}>Điểm gợi ý:</Text>
                    <Text style={styles.aiScoreValue}>
                      {grading.ai_suggested_score}/{grading.max_score}
                    </Text>
                  </View>

                  {grading.ai_feedback && (
                    <View style={styles.aiFeedbackBox}>
                      <Text style={styles.aiFeedbackText}>
                        {typeof grading.ai_feedback === 'string'
                          ? grading.ai_feedback
                          : JSON.stringify(grading.ai_feedback, null, 2)}
                      </Text>
                    </View>
                  )}
                </View>
              )}

            {grading.ai_status === 'processing' && (
              <View style={styles.aiProcessing}>
                <ActivityIndicator size="small" color="#8B5CF6" />
                <Text style={styles.aiProcessingText}>
                  AI đang chấm điểm...
                </Text>
              </View>
            )}
          </View>
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
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F0F4F8',
    padding: 24,
  },
  errorIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  errorText: {
    fontSize: 16,
    color: '#64748B',
    marginBottom: 16,
  },
  retryButton: {
    backgroundColor: '#0D47A1',
    paddingHorizontal: 24,
    paddingVertical: 10,
    borderRadius: 6,
  },
  retryButtonText: {
    color: '#FFFFFF',
    fontWeight: '600',
  },
  header: {
    backgroundColor: '#0D47A1',
    paddingTop: (StatusBar.currentHeight || 0) + 12,
    paddingBottom: 16,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 6,
    backgroundColor: 'rgba(255,255,255,0.2)',
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
    fontSize: 20,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  headerSubtitle: {
    fontSize: 13,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 2,
  },
  content: {
    flex: 1,
    padding: 16,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 16,
    marginBottom: 12,
    borderLeftWidth: 3,
    borderLeftColor: '#1565C0',
    shadowColor: '#0D47A1',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.06,
    shadowRadius: 3,
    elevation: 2,
  },
  cardDanger: {
    borderLeftWidth: 3,
    borderLeftColor: '#EF4444',
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 12,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 8,
  },
  infoLabel: {
    fontSize: 14,
    color: '#64748B',
  },
  infoValue: {
    fontSize: 14,
    fontWeight: '500',
    color: '#0F172A',
  },
  textDanger: {
    color: '#EF4444',
    fontWeight: '600',
  },
  divider: {
    height: 1,
    backgroundColor: '#F0F4F8',
  },
  descriptionText: {
    fontSize: 14,
    color: '#334155',
    lineHeight: 22,
  },
  fileItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 10,
    backgroundColor: '#F0F4F8',
    borderRadius: 6,
    marginBottom: 8,
  },
  fileIcon: {
    fontSize: 24,
    marginRight: 10,
  },
  fileInfo: {
    flex: 1,
  },
  fileName: {
    fontSize: 13,
    fontWeight: '500',
    color: '#334155',
  },
  fileSize: {
    fontSize: 11,
    color: '#94A3B8',
    marginTop: 2,
  },
  downloadIcon: {
    fontSize: 16,
  },
  // Submission status
  submissionStatusBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 6,
    marginBottom: 12,
  },
  bannerSuccess: {
    backgroundColor: '#D1FAE5',
  },
  bannerWarning: {
    backgroundColor: '#FEF3C7',
  },
  bannerIcon: {
    fontSize: 24,
    marginRight: 12,
  },
  bannerTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#0F172A',
  },
  bannerSubtitle: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 2,
  },
  attachmentsSection: {
    marginTop: 8,
  },
  sectionLabel: {
    fontSize: 13,
    fontWeight: '500',
    color: '#64748B',
    marginBottom: 8,
  },
  // Submit button
  submitButton: {
    backgroundColor: '#0D47A1',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 16,
    borderRadius: 6,
    marginBottom: 12,
  },
  submitButtonIcon: {
    fontSize: 20,
    marginRight: 8,
  },
  submitButtonText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  // Grading
  scoreSection: {
    alignItems: 'center',
    paddingVertical: 8,
  },
  scoreCircle: {
    flexDirection: 'row',
    alignItems: 'baseline',
    marginBottom: 4,
  },
  scoreValue: {
    fontSize: 36,
    fontWeight: '700',
    color: '#10B981',
  },
  scoreMax: {
    fontSize: 18,
    fontWeight: '500',
    color: '#94A3B8',
  },
  scorePercentage: {
    fontSize: 16,
    fontWeight: '600',
    color: '#64748B',
    marginBottom: 12,
  },
  feedbackBox: {
    width: '100%',
    backgroundColor: '#F0FDF4',
    borderRadius: 6,
    padding: 12,
    marginTop: 8,
  },
  feedbackLabel: {
    fontSize: 13,
    fontWeight: '600',
    color: '#334155',
    marginBottom: 6,
  },
  feedbackText: {
    fontSize: 14,
    color: '#334155',
    lineHeight: 22,
  },
  pendingGrading: {
    alignItems: 'center',
    paddingVertical: 16,
  },
  pendingIcon: {
    fontSize: 32,
    marginBottom: 8,
  },
  pendingText: {
    fontSize: 14,
    color: '#64748B',
  },
  // AI Section
  aiSection: {
    marginTop: 16,
    padding: 12,
    backgroundColor: '#E3F2FD',
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#BBDEFB',
  },
  aiHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  aiHeaderIcon: {
    fontSize: 18,
    marginRight: 6,
  },
  aiHeaderTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#0D47A1',
  },
  aiScoreRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  aiScoreLabel: {
    fontSize: 13,
    color: '#64748B',
  },
  aiScoreValue: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0D47A1',
  },
  aiFeedbackBox: {
    backgroundColor: '#FFFFFF',
    borderRadius: 4,
    padding: 10,
  },
  aiFeedbackText: {
    fontSize: 13,
    color: '#334155',
    lineHeight: 20,
  },
  aiProcessing: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 16,
  },
  aiProcessingText: {
    fontSize: 14,
    color: '#1565C0',
    marginLeft: 8,
  },
  bottomSpace: {
    height: 40,
  },
});

export default AssignmentDetailScreen;
